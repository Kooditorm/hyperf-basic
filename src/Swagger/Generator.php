<?php

declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basict/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic\Swagger;

use Hyperf\Codec\Json;
use Hyperf\Swagger\Generator as HyperfGenerator;
use InvalidArgumentException;
use OpenApi\Processors;

class Generator extends HyperfGenerator
{
    public function generate(): void
    {

        $paths = $this->config->get('swagger.scan.paths', null);
        if ($paths === null) {
            $paths = $this->config->get('annotations.scan.paths', []);
        }

        $userProcessors = $this->config->get('swagger.processors', []);
        if (!is_array($userProcessors)) {
            throw new InvalidArgumentException('The processors of swagger must be array.');
        }

        $servers = (array)$this->config->get('swagger.server', []);

        $generator = new \OpenApi\Generator();
        $openapi = $generator->setAliases(\OpenApi\Generator::DEFAULT_ALIASES)
            ->setNamespaces(\OpenApi\Generator::DEFAULT_NAMESPACES)
            ->setProcessors([
                new Processors\DocBlockDescriptions(),
                new Processors\MergeIntoOpenApi(),
                new Processors\MergeIntoComponents(),
                new Processors\ExpandClasses(),
                new Processors\ExpandInterfaces(),
                new Processors\ExpandTraits(),
                new Processors\ExpandEnums(),
                new Processors\AugmentSchemas(),
                new Processors\AugmentProperties(),
                new BuildPathsProcessor(),
                new Processors\AugmentParameters(),
                new Processors\AugmentRefs(),
                new Processors\MergeJsonContent(),
                new Processors\MergeXmlContent(),
                new Processors\OperationId(),
                new Processors\CleanUnmerged(),
                ...$userProcessors,
            ])->generate($paths, validate: false);

        $jsonArray = Json::decode($openapi->toJson());
        $paths = $jsonArray['paths'] ?? [];
        $jsonArray['paths'] = [];
        $result = [];
        foreach ($paths as $key => $path) {
            [$serverName, $key] = explode('|', $key, 2);
            if (empty($result[$serverName])) {
                $result[$serverName] = $jsonArray;
            }

            if ($svs = $servers[$serverName]['servers'] ?? null) {
                $result[$serverName]['servers'] = $svs;
            }

            if ($info = $servers[$serverName]['info'] ?? null) {
                $result[$serverName]['info'] = $info;
            }

            $result[$serverName]['paths'][$key] = $path;
        }

        $path = $this->config->get('swagger.json_dir', BASE_PATH . '/storage/swagger');
        $apiFile = '';
        $apiFileList = [];
        foreach ($result as $serverName => $json) {
            if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }

            if (empty($apiFile)){
                $apiFile = $serverName;
            }
            $apiFileList[] = $serverName . '.json';
            file_put_contents(rtrim($path, '/') . '/' . $serverName . '.json', Json::encode($json));
        }

        $apiFileListJs = implode(',', array_map(static fn (string $file) => "'{$file}'", $apiFileList));

        $htmlFile = $this->getHtmlTemplate();
        $html = file_get_contents($htmlFile);
        $html = str_replace(['{{$apifile}}', '{{$apiFileList}}'], [$apiFile, $apiFileListJs], $html);
        file_put_contents(rtrim($path, '/') . '/index.html', $html);
    }

    public function getHtmlTemplate(): string
    {
        $htmlTemplate = $this->config->get('swagger.template', 'default');
        return is_file($htmlTemplate) ? $htmlTemplate : __DIR__ . '/stubs/' . $htmlTemplate . '.stub';
    }
}