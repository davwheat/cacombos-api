<?php

namespace App\Http\Controllers;

use App\Repositories\TokensRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class ParseLogController extends JsonController
{
    protected TokensRepository $tokensRepository;

    public const VALID_LOG_FORMATS = [
        'nsg' => 'N',
    ];

    public function __construct(TokensRepository $tokenRepository)
    {
        $this->tokensRepository = $tokenRepository;

        parent::__construct();
    }

    public function handle(ServerRequestInterface $request): array | string | int | bool | null
    {
        $token = $request->getHeader('X-Auth-Token')[0] ?? null;

        $this->tokensRepository->assertValidToken($token);

        $body = array_merge($request->getParsedBody(), $request->getUploadedFiles());

        $fileOrStringValidator = function ($attribute, $value, $fail) {
            if (!is_string($value) && !($value instanceof UploadedFileInterface)) {
                $fail('The ' . $attribute . ' must either be a string or file.');
            }
        };

        $validator = Validator::make($body, [
            'logFormat' => ['required', 'string', Rule::in(array_keys(self::VALID_LOG_FORMATS))],
            'eutraLog' => ['required_without_all:eutranrLog,nrLog', $fileOrStringValidator],
            'eutranrLog' => ['required_without_all:eutraLog,nrLog', $fileOrStringValidator],
            'nrLog' => ['required_without_all:eutraLog,eutranrLog', $fileOrStringValidator],
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
            $this->response = $this->response->withStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

            return [
                'errors' => [
                    'detail' => 'Parser failed to execute with the provided log files.'
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

    public function getParserType(string $format): ?string
    {
        // Must include all `VALID_LOG_TYPES`
        $converted = self::VALID_LOG_FORMATS[$format] ?? null;

        return $converted;
    }

    /**
     * Returns an array of lines from the CLI output of the parser.
     */
    public function callParser(string $logFormat, array $logs): array
    {
        $formatFlag = $this->getParserType($logFormat);

        if ($formatFlag === null) {
            throw new \Exception('Invalid log format.');
        }

        $output = [];
        $return = 0;

        $pathToParserJar = base_path('executables/log-parser/uecapabilityparser.jar');

        $filePaths = $this->writeLogsToTempFiles($logs);

        try {
            $options = "--csv --type $formatFlag";

            $logPassed = false;

            if (Arr::has($filePaths, 'eutraLog')) {
                $options .= " -i " . escapeshellarg($filePaths['eutraLog']);
                $logPassed = true;
            }

            if (Arr::has($filePaths, 'eutranrLog')) {
                $options .= " -inputENDC " . escapeshellarg($filePaths['eutranrLog']);
                $logPassed = true;
            }

            if (Arr::has($filePaths, 'nrLog')) {
                $options .= " -inputNR " . escapeshellarg($filePaths['nrLog']);
                $logPassed = true;
            }

            if (!$logPassed) {
                throw new \Exception('No log files provided to be parsed.');
            }

            $command = escapeshellcmd(sprintf('java -jar %s %s', escapeshellarg($pathToParserJar), $options));
            $command .= ' 2>&1';

            $result = exec($command, $output, $return);

            $this->cleanUpTempFiles($filePaths);

            return ['code' => $return, 'output' => $output];
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
}
