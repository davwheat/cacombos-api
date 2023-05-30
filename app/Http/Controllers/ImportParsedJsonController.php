<?php

namespace App\Http\Controllers;

use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\Device;
use App\Models\LteComponent;
use App\Models\NrComponent;
use App\RequiresAuthentication;
use App\Rules\FileOrString;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\ServerRequestInterface;
use League\Csv\Reader;
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

    public function handle(ServerRequestInterface $request): array | string | int | bool | null
    {
        ($this->requiresAuthentication)($request, 'uploader', true);

        $body = array_merge($request->getParsedBody(), $request->getUploadedFiles());

        $validator = Validator::make($body, [
            'jsonData' => ['required', new FileOrString()],
            'deviceId' => 'required|exists:devices,id',
            'capabilitySetId' => 'required|exists:capability_sets,id',
        ]);

        if ($validator->fails()) {
            $this->response = $this->response->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

            return [
                'errors' => $validator->errors()->jsonSerialize()
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
                        'The selected capability set is invalid.'
                    ]
                ]
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
        $eutraCsv = Arr::get($csvData, 'eutraCsv');
        $eutranrCsv = Arr::get($csvData, 'eutranrCsv');
        $nrCsv = Arr::get($csvData, 'nrCsv');

        if (!empty($eutraCsv)) {
            clock()->event('Parsing EUTRA CSV')->begin();
            $this->parseEutraCsvToModels($eutraCsv);
            clock()->event('Parsing EUTRA CSV')->end();
        }

        if (!empty($eutranrCsv)) {
            clock()->event('Parsing EUTRA-NR CSV')->begin();
            $this->parseEutraNrCsvToModels($eutranrCsv);
            clock()->event('Parsing EUTRA-NR CSV')->end();
        }

        if (!empty($nrCsv)) {
            clock()->event('Parsing NR CSV')->begin();
            $this->parseNrCsvToModels($nrCsv);
            clock()->event('Parsing NR CSV')->end();
        }
    }

    protected function parseNrBandsJsonToModels(array $nrBands)
    {
        $nrBands = collect($nrBands);

        $nrBands->each(function ($nrBand) {
        });
    }

    protected function parseEutraCsvToModels(string $csvData): void
    {
        $csv = Reader::createFromString($csvData);
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);

        $header = $csv->getHeader();
        $records = $csv->getRecords();

        $combos = [];

        foreach ($records as $comboData) {
            /** @var Combo $combo */
            $combo = Combo::firstOrCreate(
                [
                    'combo_string' => $comboData['combo'],
                    'bandwidth_combination_set' => json_encode(explode(', ', $comboData['bsc'])),
                    'capability_set_id' => $this->capabilitySet->id,
                ],
                [
                    'bandwidth_combination_set' => explode(', ', $comboData['bsc']),
                ]
            );

            $combos[] = $combo;

            $ccs = [];

            for ($i = 1; $i <= 6; $i++) {
                $ccData = $this->getArrayKeyValEndingIn(strval($i), $comboData);

                if (empty($ccData)) {
                    continue;
                }

                if ($ccData['DLmod'] === 'null') {
                    $ccData['DLmod'] = "64qam";
                }

                if ($ccData['ULmod'] === 'null') {
                    $ccData['ULmod'] = "16qam";
                }

                if (empty($ccData['mimo'])) {
                    $ccData['mimo'] = 1;
                }

                $ccs[] = LteComponent::firstOrCreate(
                    [
                        'band' => $ccData['band'],
                        'dl_class' => $ccData['class'],
                        'mimo' => $ccData['mimo'],
                        'ul_class' => $ccData['ul'],
                        'dl_modulation' => $ccData['DLmod'],
                        'ul_modulation' => $ccData['ULmod'],
                        'component_index' => $i - 1,
                    ]
                );
            }

            // remove all attached models
            $combo->lteComponents()->detach();
            $combo->nrComponents()->detach();

            // attach new models
            $combo->lteComponents()->saveMany($ccs);
        }

        $this->removeCombosFromDeletion($combos);
    }

    protected function parseEutraNrCsvToModels(string $csvData): void
    {
        $csv = Reader::createFromString($csvData);
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);

        $header = $csv->getHeader();
        $records = $csv->getRecords();

        $combos = [];

        foreach ($records as $comboData) {
            /** @var Combo $combo */
            $combo = Combo::firstOrCreate(
                [
                    'combo_string' => $comboData['combo'],
                    'bandwidth_combination_set' => null,
                    'capability_set_id' => $this->capabilitySet->id,
                ]
            );

            // $combo = new Combo();
            $combos[] = $combo;

            $lteCCs = [];
            $nrCCs = [];

            // LTE DL
            for ($i = 1; $i <= 6; $i++) {
                $ccData = $this->getArrayKeyValEndingIn(strval($i), $comboData);

                if (empty($ccData) || empty($ccData['DL'])) {
                    continue;
                }

                $lteCCs[] = LteComponent::firstOrCreate(
                    [
                        'band' => intval($ccData['DL']), // "7A" -> "7"
                        'dl_class' => substr($ccData['DL'], -1, 1), // "7A" -> "A"
                        'mimo' => $ccData['mimo DL'],
                        'ul_class' => null,
                        'dl_modulation' => null,
                        'ul_modulation' => null,
                        'component_index' => $i - 1,
                    ]
                );
            }

            // LTE UL
            for ($i = 1; $i <= 2; $i++) {
                $ccData = $this->getArrayKeyValEndingIn(strval($i), $comboData);

                if (empty($ccData) || empty($ccData['UL'])) {
                    continue;
                }

                $lteCCs[] = LteComponent::firstOrCreate(
                    [
                        'band' => intval($ccData['UL']), // "7A" -> "7"
                        'dl_class' => null,
                        'ul_class' => substr($ccData['UL'], -1, 1), // "7A" -> "A"
                        'dl_modulation' => null,
                        'ul_modulation' => strtolower($ccData['MOD UL']), // "64QAM" -> "64qam"
                        'component_index' => $i - 1,
                    ]
                );
            }

            // NR DL CCs
            for ($i = 1; $i <= 9; $i++) {
                $ccData = $this->getArrayKeyValEndingIn(strval($i), $comboData);

                if (empty($ccData) || empty($ccData['NR DL'])) {
                    continue;
                }

                $nrCCs[] = NrComponent::firstOrCreate(
                    [
                        'band' => intval($ccData['NR DL']), // "78A" -> "78"
                        'dl_class' => substr($ccData['NR DL'], -1, 1),  // "78A" -> "A"
                        'ul_class' => null,
                        'bandwidth' => $ccData['NR BW'],
                        'subcarrier_spacing' => $ccData['NR SCS'],
                        'dl_mimo' => $ccData['mimo NR DL'],
                        'ul_mimo' => null,
                        'dl_modulation' => 'qam256',
                        'ul_modulation' => null,
                        'component_index' => $i - 1,
                    ]
                );
            }

            // NR UL CCs
            for ($i = 1; $i <= 4; $i++) {
                $ccData = $this->getArrayKeyValEndingIn(strval($i), $comboData);

                if (empty($ccData) || empty($ccData['NR UL'])) {
                    continue;
                }

                $nrCCs[] = NrComponent::firstOrCreate(
                    [
                        'band' => intval($ccData['NR UL']), // "78A" -> "78"
                        'dl_class' => null,
                        'ul_class' => substr($ccData['NR UL'], -1, 1), // "78A" -> "A"
                        // 'bandwidth' => $ccData['NR BW'],
                        'bandwidth' => null,
                        'subcarrier_spacing' => $ccData['NR SCS'],
                        'dl_mimo' => null,
                        'ul_mimo' => $ccData['mimo NR UL'],
                        'dl_modulation' => null,
                        'ul_modulation' => $ccData['NR UL MOD'],
                        'component_index' => $i - 1,
                    ]
                );
            }

            // remove all attached models
            $combo->lteComponents()->detach();
            $combo->nrComponents()->detach();

            // attach new models
            $combo->lteComponents()->saveMany($lteCCs);
            $combo->nrComponents()->saveMany($nrCCs);
        }

        $this->removeCombosFromDeletion($combos);
    }

    protected function parseNrCsvToModels(string $csvData): void
    {
        $csv = Reader::createFromString($csvData);
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);

        $header = $csv->getHeader();
        $records = $csv->getRecords();

        $combos = [];

        foreach ($records as $comboData) {
            /** @var Combo $combo */
            $combo = Combo::firstOrCreate(
                [
                    'combo_string' => $comboData['combo'],
                    'bandwidth_combination_set' => null,
                    'capability_set_id' => $this->capabilitySet->id,
                ]
            );

            $combos[] = $combo;

            $nrCCs = [];

            // NR DL CCs
            for ($i = 1; $i <= 9; $i++) {
                $ccData = $this->getArrayKeyValEndingIn(strval($i), $comboData);

                if (empty($ccData) || empty($ccData['NR DL'])) {
                    continue;
                }

                $nrCCs[] = NrComponent::firstOrCreate(
                    [
                        'band' => intval($ccData['NR DL']), // "78A" -> "78"
                        'dl_class' => substr($ccData['NR DL'], -1, 1),  // "78A" -> "A"
                        'ul_class' => null,
                        'bandwidth' => $ccData['NR BW'],
                        'subcarrier_spacing' => $ccData['NR SCS'],
                        'dl_mimo' => $ccData['mimo NR DL'],
                        'ul_mimo' => null,
                        'dl_modulation' => 'qam256',
                        'ul_modulation' => null,
                        'component_index' => $i - 1,
                    ]
                );
            }

            // NR UL CCs
            for ($i = 1; $i <= 4; $i++) {
                $ccData = $this->getArrayKeyValEndingIn(strval($i), $comboData);

                if (empty($ccData) || empty($ccData['NR UL'])) {
                    continue;
                }

                $nrCCs[] = NrComponent::firstOrCreate(
                    [
                        'band' => intval($ccData['NR UL']), // "78A" -> "78"
                        'dl_class' => null,
                        'ul_class' => substr($ccData['NR UL'], -1, 1), // "78A" -> "A"
                        'bandwidth' => $ccData['NR BW'],
                        'subcarrier_spacing' => $ccData['NR SCS'],
                        'dl_mimo' => null,
                        'ul_mimo' => $ccData['mimo NR UL'],
                        'dl_modulation' => null,
                        'ul_modulation' => $ccData['NR UL MOD'],
                        'component_index' => $i - 1,
                    ]
                );
            }

            // remove all attached models
            $combo->lteComponents()->detach();
            $combo->nrComponents()->detach();

            // attach new models
            $combo->nrComponents()->saveMany($nrCCs);
        }

        $this->removeCombosFromDeletion($combos);
    }

    protected function getArrayKeyValEndingIn(string $val, array $arr): ?array
    {
        $keys = array_keys($arr);
        $keys = array_filter($keys, fn ($key) => str_ends_with($key, $val));

        $newArr = [];

        foreach ($keys as $key) {
            $newArr[substr($key, 0, strlen($key) - 1)] = $arr[$key];
        }

        $nonEmptyVals = array_filter($newArr, fn ($val) => !empty($val));

        if (count($nonEmptyVals) === 0) {
            return null;
        }

        return $newArr;
    }
}
