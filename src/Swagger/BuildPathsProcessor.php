<?php

declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basic/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic\Swagger;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Processor\BuildPathsProcessor as HyperfBuildPathsProcessor;
use Kooditorm\Hyperf\Basic\Annotations\Swagger\SwaggerService;
use OpenApi\Annotations as OA;
use OpenApi\Analysis;
use OpenApi\Context;
use OpenApi\Generator;

class BuildPathsProcessor extends HyperfBuildPathsProcessor
{
    public function __invoke(Analysis $analysis)
    {
        $paths = [];
        // Merge @OA\PathItems with the same path.
        if (! Generator::isDefault($analysis->openapi->paths)) {
            foreach ($analysis->openapi->paths as $annotation) {
                if (empty($annotation->path)) {
                    $annotation->_context->logger->warning($annotation->identity() . ' is missing required property "path" in ' . $annotation->_context);
                } elseif (isset($paths[$annotation->path])) {
                    $paths[$annotation->path]->mergeProperties($annotation);
                    $analysis->annotations->detach($annotation);
                } else {
                    $paths[$annotation->path] = $annotation;
                }
            }
        }

        /** @var OA\Operation[] $operations */
        $operations = $analysis->unmerged()->getAnnotationsOfType(OA\Operation::class);

        // Merge @OA\Operations into existing @OA\PathItems or create a new one.
        foreach ($operations as $operation) {
            $class = $operation->_context->namespace . '\\' . $operation->_context->class;
            /** @var null|HyperfServer|SwaggerService $serverAnnotation */
            $serverAnnotation = AnnotationCollector::getClassAnnotation($class, SwaggerService::class);

            if (! $serverAnnotation) {
                $serverAnnotation = AnnotationCollector::getClassAnnotation($class, HyperfServer::class);
            }

            if (! $serverAnnotation) {
                continue;
            }

            $path = $serverAnnotation->name . '|' . $operation->path;

            if (empty($paths[$path])) {
                $paths[$path] = $pathItem = new OA\PathItem(
                    [
                        'path' => $path,
                        '_context' => new Context(['generated' => true], $operation->_context),
                    ]
                );
                $analysis->addAnnotation($pathItem, $pathItem->_context);
            }
            if ($paths[$path]->merge([$operation])) {
                $operation->_context->logger->warning('Unable to merge ' . $operation->identity() . ' in ' . $operation->_context);
            }
        }
        if ($paths) {
            $analysis->openapi->paths = array_values($paths);
        }
    }
}