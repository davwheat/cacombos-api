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

class ImportParsedCsvController extends JsonController
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
            'eutraCsv' => ['required_with:eutranrCsv|required_without:nrCsv', new FileOrString()],
            'eutranrCsv' => ['required_without_all:eutraCsv,nrCsv', new FileOrString()],
            'nrCsv' => ['required_without_all:eutraCsv,eutranrCsv', new FileOrString()],
            'deviceId' => 'required|exists:devices,id',
            'capabilitySetId' => 'required|exists:capability_sets,id',
        ]);

        if ($validator->fails()) {
            $this->response = $this->response->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

            return [
                'errors' => $validator->errors()->jsonSerialize()
            ];
        }

        $csvData = Arr::only($body, ['eutraCsv', 'eutranrCsv', 'nrCsv']);

        # For each CSV, if they are files, convert to strings
        foreach ($csvData as $key => $csv) {
            if (!is_string($csv)) {
                $csvData[$key] = $csv->getStream()->getContents();
            }
        }

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

        $this->propogateCombosToDelete($csvData);

        DB::transaction(function () use ($csvData) {
            $this->parseCsvsToModels($csvData);

            // Delete unneeded combos
            Combo::whereIn('id', $this->combosToDelete->pluck('id'))->delete();
            $this->combosToDelete = $this->combosToDelete->empty();
        });

        return null;
    }

    protected function propogateCombosToDelete(array $csvData): void
    {
        $comboQuery = $this->capabilitySet->combos();

        // Remove unlinked combos
        $this->combosToDelete = $this->combosToDelete->merge(
            $comboQuery
                ->clone()
                ->doesntHave('lteComponents')
                ->doesntHave('nrComponents')
                ->get('id')
        );

        if (Arr::has($csvData, 'eutraCsv')) {
            // replacing EUTRA data
            $this->combosToDelete = $this->combosToDelete->merge(
                $comboQuery
                    ->clone()
                    ->has('lteComponents')
                    ->doesntHave('nrComponents')
                    ->get('id')
            );
        }

        if (Arr::has($csvData, 'eutranrCsv')) {
            // replacing EUTRA-NR data
            $this->combosToDelete = $this->combosToDelete->merge(
                $comboQuery
                    ->clone()
                    ->has('lteComponents')
                    ->has('nrComponents')
                    ->get('id')
            );
        }

        if (Arr::has($csvData, 'nrCsv')) {
            // replacing NR data
            $this->combosToDelete = $this->combosToDelete->merge(
                $comboQuery
                    ->clone()
                    ->doesntHave('lteComponents')
                    ->has('nrComponents')
                    ->get('id')
            );
        }
    }

    protected function removeCombosFromDeletion(array|Collection|Combo $combos)
    {
        $this->combosToDelete = $this->combosToDelete->diff(
            $combos instanceof Collection ? $combos : collect($combos)
        );
    }

    protected function parseCsvsToModels(array $csvData): void
    {
        $eutraCsv = Arr::get($csvData, 'eutraCsv');
        $eutranrCsv = Arr::get($csvData, 'eutranrCsv');
        $nrCsv = Arr::get($csvData, 'nrCsv');

        if (!empty($eutraCsv)) {
            $this->parseEutraCsvToModels($eutraCsv);
        }

        if (!empty($eutranrCsv)) {
            $this->parseEutraNrCsvToModels($eutranrCsv);
        }

        if (!empty($nrCsv)) {
            $this->parseNrCsvToModels($nrCsv);
        }
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
