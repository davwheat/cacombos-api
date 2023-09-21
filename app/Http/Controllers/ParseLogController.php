<?php

namespace App\Http\Controllers;

use App\RequiresAuthentication;
use App\Rules\FileOrString;
use BeyondCode\ServerTiming\Facades\ServerTiming;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ParseLogController extends JsonController
{
    protected RequiresAuthentication $requiresAuthentication;

    public const VALID_LOG_FORMATS = [
        'nsg'      => 'N',
        'qualcomm' => null,

        // Internal use only
        'qualcomm-lte' => 'Q',
        'qualcomm-nr'  => 'QNR',
    ];

    public function __construct(RequiresAuthentication $requiresAuthentication)
    {
        $this->requiresAuthentication = $requiresAuthentication;

        parent::__construct();
    }

    public function handle(ServerRequestInterface $request): array|string|int|bool|null
    {
        ($this->requiresAuthentication)($request, 'parser', true);

        $body = array_merge($request->getParsedBody(), $request->getUploadedFiles());

        $validator = Validator::make($body, [
            'logFormat'  => ['required', 'string', Rule::in(array_keys(self::VALID_LOG_FORMATS))],
            'eutraLog'   => ['required_without_all:eutranrLog,nrLog', new FileOrString()],
            'eutranrLog' => ['required_without_all:eutraLog,nrLog', new FileOrString()],
            'nrLog'      => ['required_without_all:eutraLog,eutranrLog', new FileOrString()],
        ]);

        if ($validator->fails()) {
            $this->response = $this->response->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

            return [
                'errors' => $validator->errors()->jsonSerialize(),
            ];
        }

        $logFormat = Arr::get($body, 'logFormat');
        $logs = Arr::only($body, ['eutraLog', 'eutranrLog', 'nrLog']);

        $output = [];

        if ($logFormat === 'qualcomm') {
            // Special case: nrLog contains ENDC and NR data
            $lteLogs = Arr::only($logs, ['eutraLog']);
            if (count($lteLogs) > 0) {
                $output[] = $this->callParser('qualcomm-lte', $lteLogs);
            }

            $nrLogs = Arr::only($logs, ['nrLog']);
            if (count($nrLogs) > 0) {
                $output[] = $this->callParser('qualcomm-nr', $nrLogs);
            }
        } else {
            $output[] = $this->callParser($logFormat, $logs);
        }

        // If an error occurred in any of the parser calls, return the error.
        if (count(array_filter($output, fn ($out) => $out['code'] !== 0)) > 0) {
            $debug = App::hasDebugModeEnabled();
            $this->response = $this->response->withStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

            return [
                'errors' => [
                    'detail' => 'Parser failed to execute with the provided log files.',
                    'meta'   => !$debug ? null : $output,
                ],
            ];
        }

        foreach ($output as &$out) {
            $outputLines = $out['output'];

            Arr::forget($outputLines, ['logType', 'parserVersion', 'timestamp', 'metadata']);

            // Error if one or more parser call outputs have no capability data
            if (count($outputLines) === 0) {
                $this->response = $this->response->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

                return [
                    'errors' => [
                        'detail' => 'Parser failed to find any capability data in one or more of the provided log files.',
                        'meta'   => $out['output'],
                    ],
                ];
            }
        }

        $this->response = $this->response->withHeader('Content-Type', 'application/json');

        $out = array_map(fn ($out) => $out['output'], $output);

        return $out;
    }

    public function getParserType(string $format): string
    {
        // Must include all `VALID_LOG_TYPES`
        $converted = self::VALID_LOG_FORMATS[$format] ?? null;

        if ($converted === null) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid log format.');
        }

        return $converted;
    }

    /**
     * Returns an array of lines from the CLI output of the parser.
     */
    public function callParser(string $logFormat, array $logs): array
    {
        ServerTiming::start('Running log parser');

        $output = [];

        $filePaths = $this->writeLogsToTempFiles($logs);

        try {
            $options = [];

            $options[] = ['--json', '-'];

            $logPassed = false;

            if (Arr::has($filePaths, 'eutraLog')) {
                $options[] = ['--input', escapeshellarg($filePaths['eutraLog'])];
                $logPassed = true;
            }

            if (Arr::has($filePaths, 'eutranrLog')) {
                $options[] = ['--inputENDC', escapeshellarg($filePaths['eutranrLog'])];
                $logPassed = true;
            }

            if (Arr::has($filePaths, 'nrLog')) {
                $options[] = ['--inputNR', escapeshellarg($filePaths['nrLog'])];
                $logPassed = true;
            }

            if (!$logPassed) {
                throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'No log files provided to be parsed.');
            }

            $output = $this->executeParser($logFormat, $options);

            $this->cleanUpTempFiles($filePaths);

            return $output;
        } catch (\Exception $e) {
            $this->cleanUpTempFiles($filePaths);

            ServerTiming::stop('Running log parser');

            throw $e;
        }

        ServerTiming::stop('Running log parser');
    }

    public function writeLogsToTempFiles(array $logs): array
    {
        $tempFiles = [];

        /**
         * @var string                       $key
         * @var UploadedFileInterface|string $log
         */
        foreach ($logs as $key => $log) {
            $tempFile = tempnam(sys_get_temp_dir(), 'log-parser-');
            file_put_contents($tempFile, is_string($log) ? $log : $log->getStream());
            $tempFiles[$key] = $tempFile;
        }

        return $tempFiles;
    }

    public function cleanUpTempFiles(array $tempFiles): void
    {
        foreach ($tempFiles as $tempFile) {
            unlink($tempFile);
        }
    }

    public function executeParser(string $logFormat, array $options): array
    {
        $formatFlag = $this->getParserType($logFormat);

        if ($formatFlag === null) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid log format.');
        }

        $options[] = ['--type', $formatFlag];
        $options = $this->transformOptions($logFormat, $options);
        $options = implode(' ', Arr::flatten($options));

        $pathToParserJar = base_path('executables/log-parser/uecapabilityparser.jar');

        $command = escapeshellcmd(sprintf('java -jar %s %s', escapeshellarg($pathToParserJar), $options));
        $command .= ' 2>&1';

        exec($command, $output, $return);

        return ['code' => $return, 'output' => json_decode(Arr::join($output, PHP_EOL), true)];
    }

    private function transformOptions(string $type, array $options): array
    {
        switch ($type) {
            case 'qualcomm-nr':
                $options[] = ['--multiple0xB826'];

                // Rename inputNR option to input
                $options = array_map(function ($option) {
                    if ($option[0] === '--inputNR') {
                        $option[0] = '--input';
                    }

                    return $option;
                }, $options);
                break;
        }

        return $options;
    }
}
