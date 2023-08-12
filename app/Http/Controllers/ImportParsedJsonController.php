<?php

namespace App\Http\Controllers;

use App\DataParser\EndcParser;
use App\DataParser\LteCaParser;
use App\DataParser\NrCaParser;
use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\Device;
use App\RequiresAuthentication;
use App\Rules\FileOrString;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class ImportParsedJsonController extends JsonController
{
    protected RequiresAuthentication $requiresAuthentication;
    protected Collection $combosToDelete;

    /**
     * @var CapabilitySet
     */
    protected $capabilitySet;

    public function __construct(RequiresAuthentication $requiresAuthentication)
    {
        $this->requiresAuthentication = $requiresAuthentication;
        $this->combosToDelete = new Collection();

        parent::__construct();
    }

    public function handle(ServerRequestInterface $request): array|string|int|bool|null
    {
        ($this->requiresAuthentication)($request, 'uploader', true);

        $body = array_merge($request->getParsedBody(), $request->getUploadedFiles());

        $validator = Validator::make($body, [
            'jsonData'        => ['required', new FileOrString()],
            'deviceId'        => 'required|exists:devices,id',
            'capabilitySetId' => 'required|exists:capability_sets,id',
        ]);

        if ($validator->fails()) {
            $this->response = $this->response->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

            return [
                'errors' => $validator->errors()->jsonSerialize(),
            ];
        }

        /** @var UploadedFileInterface|string */
        $jsonData = $body['jsonData'];

        // Convert uploaded file to string
        if (!is_string($jsonData)) {
            $jsonData = $jsonData->getStream()->getContents();
        }

        // Parse JSON data to associative array
        /** @var array */
        $jsonData = json_decode($jsonData, true);

        $deviceId = Arr::get($body, 'deviceId');
        $device = Device::findOrFail($deviceId);

        $capabilitySetId = Arr::get($body, 'capabilitySetId');
        $this->capabilitySet = CapabilitySet::with('deviceFirmware')->findOrFail($capabilitySetId);

        if ($this->capabilitySet->deviceFirmware->device_id !== $device->id) {
            $this->response = $this->response->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

            return [
                'errors' => [
                    'capabilitySetId' => [
                        'The selected capability set is invalid.',
                    ],
                ],
            ];
        }

        $this->propogateCombosToDelete($jsonData);

        DB::transaction(function () use ($jsonData) {
            $this->parseJsonToModels($jsonData);

            // Delete unneeded combos
            Combo::whereIn('id', $this->combosToDelete->pluck('id'))->delete();
            $this->combosToDelete = $this->combosToDelete->empty();
        });

        return null;
    }

    protected function propogateCombosToDelete(array $jsonData): void
    {
        clock()->event('Finding combos to remove')->begin();

        // Delete all combos currently present in the capability set
        $this->combosToDelete = $this->capabilitySet->combos()->get('id');

        clock()->event('Finding combos to remove')->end();
    }

    protected function removeCombosFromDeletion(array|Collection|Combo $combos)
    {
        clock()->event('Removing unused combos')->begin();

        $this->combosToDelete = $this->combosToDelete->diff(
            $combos instanceof Collection ? $combos : collect($combos)
        );

        clock()->event('Removing unused combos')->end();
    }

    protected function parseJsonToModels(array $jsonData): void
    {
        clock()->event('Parsing EUTRA data')->begin();
        $this->parseEutraDataToModels($jsonData);
        clock()->event('Parsing EUTRA data')->end();

        clock()->event('Parsing NR NSA data')->begin();
        $this->parseEndcDataToModels($jsonData);
        clock()->event('Parsing NR NSA data')->end();

        clock()->event('Parsing NR CA data')->begin();
        $this->parseNrcaDataToModels($jsonData);
        clock()->event('Parsing NR CA data')->end();
    }

    protected function parseEutraDataToModels(array $jsonData): void
    {
        $lteCaData = Arr::get($jsonData, 'lteca');

        if (!empty($lteCaData)) {
            clock()->event('Parsing LTE CA data')->begin();

            $lteCaParser = new LteCaParser($lteCaData, $this->capabilitySet);
            $lteCaParser->parseAndInsertAllModels();

            clock()->event('Parsing LTE CA data')->end();
        }
    }

    protected function parseEndcDataToModels(array $jsonData): void
    {
        $endcData = Arr::get($jsonData, 'endc');

        if (!empty($endcData)) {
            clock()->event('Parsing ENDC data')->begin();

            $endcParser = new EndcParser($endcData, $this->capabilitySet);
            $endcParser->parseAndInsertAllModels();

            clock()->event('Parsing ENDC data')->end();
        }
    }

    protected function parseNrcaDataToModels(array $jsonData): void
    {
        $nrcaData = Arr::get($jsonData, 'nrca');

        if (!empty($nrcaData)) {
            clock()->event("Parsing ENDC data")->begin();

            $nrcaParser = new NrCaParser($nrcaData, $this->capabilitySet);
            $nrcaParser->parseAndInsertAllModels();

            clock()->event("Parsing ENDC data")->end();
        }
    }
}
