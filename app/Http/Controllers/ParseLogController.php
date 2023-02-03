<?php

namespace App\Http\Controllers;

use App\RequiresAuthentication;
use App\Validator\FileOrStringValidator;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class ParseLogController extends JsonController
{
    protected RequiresAuthentication $requiresAuthentication;

    public const VALID_LOG_FORMATS = [
        'nsg' => 'N',
        'qualcomm' => 'QALL',
    ];

    public function __construct(RequiresAuthentication $requiresAuthentication)
    {
        $this->requiresAuthentication = $requiresAuthentication;

        parent::__construct();
    }

    public function handle(ServerRequestInterface $request): array | string | int | bool | null
    {
        ($this->requiresAuthentication)($request, 'parser');

        $body = array_merge($request->getParsedBody(), $request->getUploadedFiles());

        $validator = Validator::make($body, [
            'logFormat' => ['required', 'string', Rule::in(array_keys(self::VALID_LOG_FORMATS))],
            'eutraLog' => ['required_without_all:eutranrLog,nrLog', FileOrStringValidator::class],
            'eutranrLog' => ['required_without_all:eutraLog,nrLog', FileOrStringValidator::class],
            'nrLog' => ['required_without_all:eutraLog,eutranrLog', FileOrStringValidator::class],
        ]);

        if ($validator->fails()) {
            $this->response = $this->response->withStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

            return [
                'errors' => $validator->errors()->jsonSerialize()
            ];
        }

        $logFormat = Arr::get($body, 'logFormat');
        $logs = Arr::only($body, ['eutraLog', 'eutranrLog', 'nrLog']);

        $output = $this->callParser($logFormat, $logs);

        if ($output['code'] !== 0) {
            $debug = App::hasDebugModeEnabled();
            $this->response = $this->response->withStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

            return [
                'errors' => [
                    'detail' => 'Parser failed to execute with the provided log files.',
                    'meta' => !$debug ? null : ['output' => $output['output']]
                ]
            ];
        }

        if ($output['code'] === 0) {
            $this->response = $this->response->withHeader('Content-Type', 'text/csv');
        } else {
            $this->response = $this->response->withHeader('Content-Type', 'text/plain');
        }

        return explode(PHP_EOL . PHP_EOL, implode(PHP_EOL, $output['output']));
    }

    public function getParserType(string $format): ?array
    {
        // Must include all `VALID_LOG_TYPES`
        $converted = self::VALID_LOG_FORMATS[$format] ?? null;

        if ($converted !== null && !is_array($converted)) {
            return [$converted, $converted, $converted];
        }

        return $converted;
    }

    /**
     * Returns an array of lines from the CLI output of the parser.
     */
    public function callParser(string $logFormat, array $logs): array
    {
        $output = [];

        $filePaths = $this->writeLogsToTempFiles($logs);

        try {
            $options = [];

            $options[] = ['--csv'];

            $logPassed = false;

            if (Arr::has($filePaths, 'eutraLog')) {
                $options[] = ["-i", escapeshellarg($filePaths['eutraLog'])];
                $logPassed = true;
            }

            if (Arr::has($filePaths, 'eutranrLog')) {
                $options[] = ["-inputENDC", escapeshellarg($filePaths['eutranrLog'])];
                $logPassed = true;
            }

            if (Arr::has($filePaths, 'nrLog')) {
                $options[] = ["-inputNR", escapeshellarg($filePaths['nrLog'])];
                $logPassed = true;
            }

            if (!$logPassed) {
                throw new \Exception('No log files provided to be parsed.');
            }

            $output = $this->executeParser($logFormat, $options);

            $this->cleanUpTempFiles($filePaths);

            return $output;
        } catch (\Exception $e) {
            $this->cleanUpTempFiles($filePaths);

            throw $e;
        }
    }

    public function writeLogsToTempFiles(array $logs): array
    {
        $tempFiles = [];

        /**
         * @var string $key
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
            throw new \Exception('Invalid log format.');
        }

        $options[] = ['--type', $formatFlag];
        $options = $this->transformOptions($logFormat, $options);
        $options = implode(' ', Arr::flatten($options));

        $pathToParserJar = base_path('executables/log-parser/uecapabilityparser.jar');

        $command = escapeshellcmd(sprintf('java -jar %s %s', escapeshellarg($pathToParserJar), $options));
        $command .= ' 2>&1';

        exec($command, $output, $return);

        return ['code' => $return, 'output' => $output];
    }

    private function transformOptions(string $type, array $options): array
    {
        switch ($type) {
            case 'qualcomm':
                $options[] = ['--multi'];
                break;
        }

        return $options;
    }
}
