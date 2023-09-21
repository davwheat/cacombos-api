<?php

namespace App\Http\Controllers;

use App\DataParser\EndcParser;
use App\DataParser\LteCaParser;
use App\DataParser\LteSupportedBandsParser;
use App\DataParser\NrCaParser;
use App\DataParser\NrDcParser;
use App\DataParser\NrSupportedBandsParser;
use App\Models\CapabilitySet;
use App\Models\Device;
use App\RequiresAuthentication;
use App\Rules\FileOrString;
use BeyondCode\ServerTiming\Facades\ServerTiming;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class ImportParsedJsonController extends JsonController
{
    protected RequiresAuthentication $requiresAuthentication;

    /**
     * @var CapabilitySet
     */
    protected $capabilitySet;

    public function __construct(RequiresAuthentication $requiresAuthentication)
    {
        $this->requiresAuthentication = $requiresAuthentication;

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
            'multipleInputs'  => 'nullable|boolean',
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
        $multipleInputs = Arr::get($body, 'multipleInputs', false) === true;

        $deviceId = Arr::get($body, 'deviceId');
        $device = Device::findOrFail($deviceId);

        $capabilitySetId = Arr::get($body, 'capabilitySetId');
        $this->capabilitySet = CapabilitySet::with('device_firmware')->findOrFail($capabilitySetId);

        if ($this->capabilitySet->device_firmware->device_id !== $device->id) {
            $this->response = $this->response->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

            return [
                'errors' => [
                    'capabilitySetId' => [
                        'The selected capability set is invalid.',
                    ],
                ],
            ];
        }

        DB::transaction(function () use ($jsonData, $multipleInputs) {
            Schema::disableForeignKeyConstraints();

            ServerTiming::start('Deleting old combos');
            // Delete all combos currently present in the capability set
            $this->capabilitySet->combos()->delete();
            ServerTiming::stop('Deleting old combos');

            if (!$multipleInputs) {
                $jsonData = [$jsonData];
            }

            foreach ($jsonData as $json) {
                $this->parseJsonToModels($json);
            }

            // Merge metadata from all outputs
            $metadata = array_reduce($jsonData, function ($carry, $item) {
                $carry[] = Arr::only($item, ['metadata', 'parserVersion', 'timestamp']);

                return $carry;
            }, []);

            $this->capabilitySet->parser_metadata = $metadata;
            $this->capabilitySet->save();

            Schema::enableForeignKeyConstraints();
        });

        return null;
    }

    protected function parseJsonToModels(array $jsonData): void
    {
        $this->capabilitySet->lte_category_dl = Arr::get($jsonData, 'lteCategoryDl', null);
        $this->capabilitySet->lte_category_ul = Arr::get($jsonData, 'lteCategoryUl', null);
        $this->capabilitySet->save();

        ServerTiming::start('Parsing EUTRA data');
        $this->parseEutraDataToModels($jsonData);
        ServerTiming::stop('Parsing EUTRA data');

        ServerTiming::start('Parsing NR NSA data');
        $this->parseEndcDataToModels($jsonData);
        ServerTiming::stop('Parsing NR NSA data');

        ServerTiming::start('Parsing NR CA data');
        $this->parseNrCaDataToModels($jsonData);
        ServerTiming::stop('Parsing NR CA data');

        ServerTiming::start('Parsing NR DC data');
        $this->parseNrDcDataToModels($jsonData);
        ServerTiming::stop('Parsing NR DC data');

        ServerTiming::start('Parsing supported LTE bands');
        $this->parseSupportedLteBandsToModels($jsonData);
        ServerTiming::stop('Parsing supported LTE bands');

        ServerTiming::start('Parsing supported NR bands');
        $this->parseSupportedNrBandsToModels($jsonData);
        ServerTiming::stop('Parsing supported NR bands');
    }

    protected function parseEutraDataToModels(array $jsonData): void
    {
        $lteCaData = Arr::get($jsonData, 'lteca');

        if (!empty($lteCaData)) {
            ServerTiming::start('Parsing LTE CA data');

            $lteCaParser = new LteCaParser($lteCaData, $this->capabilitySet);
            $lteCaParser->parseAndInsertAllModels();

            ServerTiming::stop('Parsing LTE CA data');
        }
    }

    protected function parseEndcDataToModels(array $jsonData): void
    {
        $endcData = Arr::get($jsonData, 'endc');

        if (!empty($endcData)) {
            ServerTiming::start('Parsing ENDC data');

            $endcParser = new EndcParser($endcData, $this->capabilitySet);
            $endcParser->parseAndInsertAllModels();

            ServerTiming::stop('Parsing ENDC data');
        }
    }

    protected function parseNrCaDataToModels(array $jsonData): void
    {
        $nrcaData = Arr::get($jsonData, 'nrca');

        if (!empty($nrcaData)) {
            ServerTiming::start('Parsing NRCA data');

            $nrcaParser = new NrCaParser($nrcaData, $this->capabilitySet);
            $nrcaParser->parseAndInsertAllModels();

            ServerTiming::stop('Parsing NRCA data');
        }
    }

    protected function parseNrDcDataToModels(array $jsonData): void
    {
        $nrdcData = Arr::get($jsonData, 'nrdc');

        if (!empty($nrdcData)) {
            ServerTiming::start('Parsing NRDC data');

            $nrcaParser = new NrDcParser($nrdcData, $this->capabilitySet);
            $nrcaParser->parseAndInsertAllModels();

            ServerTiming::stop('Parsing NRDC data');
        }
    }

    protected function parseSupportedLteBandsToModels(array $jsonData): void
    {
        $supportedBandsData = Arr::get($jsonData, 'lteBands');

        if (!empty($supportedBandsData)) {
            ServerTiming::start('Parsing supported LTE bands data');

            $supportedBandsParser = new LteSupportedBandsParser($supportedBandsData, $this->capabilitySet);
            $supportedBandsParser->parseAndInsertAllModels();

            ServerTiming::stop('Parsing supported LTE bands data');
        }
    }

    protected function parseSupportedNrBandsToModels(array $jsonData): void
    {
        $supportedBandsData = Arr::only($jsonData, ['nrBands', 'nrNsaBandsEutra', 'nrSaBandsEutra']);

        if (count($supportedBandsData) > 0) {
            ServerTiming::start('Parsing supported NR bands data');

            // Different input required to other parsers
            $supportedBandsParser = new NrSupportedBandsParser($supportedBandsData, $this->capabilitySet);
            $supportedBandsParser->parseAndInsertAllModels();

            ServerTiming::stop('Parsing supported NR bands data');
        }
    }
}
