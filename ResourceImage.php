<?php

namespace jmoguelruiz\yii2\components;

use Imagine\Image\Box;
use Imagine\Image\ManipulatorInterface;
use jmoguelruiz\yii2\components\ResourcePath;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\imagine\Image;
use yii\web\UploadedFile;
use const YII_ENV;

/**
 * 
 * Manager of images.
 * 
 * Please put the follow code in components part of your yii2 proyect.
 * 
 * [
 *  'components' => [
 *     'resourceImage' => [
 *               'class' => 'common\components\ResourceImage',
 *               // Optional Configs
 *               'modelClasses' => [ // Custom models.
 *                   'ResourcePath' => 'common\components\ResourcePath'
 *               ],
 *               'basePaths' => [ // Base paths of your project in each enviroment.
 *                   'prod' => 'images/',
 *                   'test' => 'images/test',
 *                   'dev' => 'images/dev'
 *               ],
 *               'serverType' => ResourceImage::SERVER_LOCAL // Server type for now is only saved in same server.
 *               'prefixTemp' => 'temp', // Temp prefix for folders.
 *               'containerBasePath' => "@frontend/web", // Container base to save images.
 *               'cdn' => 'http://www.project.com', // If you want cdn.
 *               'symbolConcatTime' => '_' // Symbol to concat the name with the string time.
 *           ],
 *     ...
 *  ]
 * ]
 * 
 * @author Jose Obed Moguel Ruiz <jmoguelruiz@gmail.com>
 * 
 * @todo:
 * 
 * * Save image in the server amazon s3.
 * * Save image from web.
 * * Rename image file.
 * * Optimize images before upload.
 * 
 */
class ResourceImage extends Component
{

    /**
     * Enviroments
     */
    const ENVIROMENT_DEV = 'dev';
    const ENVIROMENT_TEST = 'test';
    const ENVIROMENT_PROD = 'prod';

    /**
     * Servers to save final images.
     */
    const SERVER_LOCAL = 'local';
    const SERVER_S3 = 's3';

    /**
     * Sizes to images.
     */
    const SIZE_ORIGINAL = 'original';
    const SIZE_THUMB = 'thumb';

    /**
     * Base paths to configure in each enviroment.
     * Default: 
     * [
     *    'dev' => 'images/dev',
     *    'test' => 'images/test',
     *    'prod' => 'images'
     * ]
     * @var arr
     */
    public $basePaths = [
        'dev' => 'images/dev',
        'test' => 'images/test',
        'prod' => 'images'
    ];

    /**
     * Type of server, to save final images.
     * Default. SERVER_LOCAL.
     * @var string
     */
    public $serverType = self::SERVER_LOCAL;

    /**
     * Prefix temp.
     * @var type 
     */
    public $prefixTemp = 'temp';

    /**
     * Symbol to concatenate the time with the name.
     * @var string
     */
    public $symbolConcatTime = '_';

    /**
     * Container base path;
     * @var type 
     */
    public $containerBasePath = "@frontend/web";

    /**
     * Url to cdn.
     * @var string 
     */
    public $cdn;

    /**
     * Custom models.
     * @var arr 
     */
    public $modelClasses = [];

    /**
     * Base configuration of url.
     * @var arr
     */
    private $baseConfigUrl;

    /**
     * Config url.
     */
    private $configUrl;

    /**
     * Concat elements neccesarys to complete the url.
     * @var string 
     */
    private $stringUrl = [
        'root' => '',
        'basePath' => '',
        'resource' => '',
        'size' => '',
        'name' => ''
    ];

    /**
     * Src path to process.
     * @var string Temporal source.
     */
    private $srcTemp = null;

    /**
     * Part path name.
     * @var string
     */
    private $partPathName = "";

    /**
     * Part path size.
     * @var string
     */
    private $partPathSize = "";

    /**
     * Part path resource.
     * @var string
     */
    private $partPathResource = "";

    /**
     * Part path basePath.
     * @var string
     */
    private $partPathBasePath = "";

    /**
     * Part path root.
     * @var string
     */
    private $partPathRoot = "";

    /**
     * inherit
     */
    public function init()
    {

        $this->modelClasses = ArrayHelper::merge([
                    'ResourcePath' => 'jmoguelruiz\yii2\components\ResourcePath'
                        ], $this->modelClasses);
    }

    /**
     * Generate new path.
     * @param arr $options Options of each part to generate path.
     * Default :
     * [
     *  'root' => ['isWebUrl' => true ],
     *  'basePath' => ['enviroment' => $this->getEnviromentRunning()], // Enviroment running
     *  'resource' => ['type' => '', 'isTemp' => false],
     *  'size' => ['type' => self::SIZE_ORIGINAL],
     *  'name' => ['title' => '', 'ext' => '' , 'concatTime' => true]
     * ]
     * 
     * @return ResourcePath
     * 
     */
    public function newPath($options = [])
    {

        $this->setConfigPartsToPath($options);

        $resourcePath = $this->model('ResourcePath');

        $resourcePath->path = $this->generateUrl();
        $resourcePath->config = $this->configUrl;
        $resourcePath->name = $this->partPathName;
        $resourcePath->basePath = $this->partPathBasePath;
        $resourcePath->resource = $this->partPathResource;
        $resourcePath->size = $this->partPathSize;
        $resourcePath->root = $this->partPathRoot;


        return $resourcePath;
    }

    /**
     * 
     * Save images by size.
     * 
     * @param string $size size.
     * @param ResourcePath $tempPath Temp path for the image.
     * @param arr $functionNames An array with the function names to will be procecceed.
     * @param arr $options Options.
     * @return true
     * 
     * Options default. 
     * [
     *  'saveOptions' => ['deleteTemp' => true]
     * ]
     * 
     * @throws Exception Function $functionName not exist
     */
    public function saveBySize($size, $tempPath, $functionNames = null, $options = [])
    {

        $options = ArrayHelper::merge([
                    'saveOptions' => ['deleteTemp' => true]
                        ], $options);

        try {

            if ($tempPath instanceof ResourcePath && $tempPath->config['resource']['isTemp']) {

                $newTempPath = $this->newPath(ArrayHelper::merge($tempPath->config, [
                            'size' => ['type' => $size]
                ]));

                $this->copy($tempPath, $newTempPath);

                $this->proccessImageWithFunctions($newTempPath, $functionNames);

                $this->save($newTempPath, $newTempPath, $options['saveOptions']);

                $this->delete($newTempPath);

                return true;
            }
        } catch (Exception $ex) {
            Yii::error($ex);
            return false;
        }
    }

    /**
     * Get web url.
     * 
     * @param string $name Name of the image.
     * @param string $resourceType Resource Type.
     * @param string $size Size.
     * @return string
     */
    public function getWebUrl($name, $resourceType, $size)
    {

        $path = $this->newPath([
            'resource' => ['type' => $resourceType],
            'size' => ['type' => $size],
            'name' => ['title' => $name, 'concatTime' => false]
        ]);

        return $path->path;
    }

    /**
     * 
     * Get default image web url.
     * 
     * @param string $resourceType Resource type.
     * @param string $size Size.
     * @return string
     */
    public function getDefaultWebUrl($resourceType, $size)
    {

        $path = $this->newPath([
            'resource' => ['type' => $resourceType],
            'size' => ['type' => $size],
            'name' => ['concatTime' => false]
        ]);

        return $path->path;
    }

    /**
     * Proccess the image in the path specified with the functions name passed.
     * @param ResourcePath $path Path.
     * @param arr $functionNames Function names to will be procceced.
     * @throws Exception Function $functionName not exist.
     */
    private function proccessImageWithFunctions($path, $functionNames = [])
    {
        if (!empty($functionNames) && $path instanceof ResourcePath)
            foreach ($functionNames as $functionName) {

                if (method_exists(get_class($this), $functionName)) {
                    $this->$functionName($path);
                } else {
                    throw new Exception("Function $functionName not exist");
                }
            }
    }

    /**
     * Save the file in non-temp path.
     * 
     * @param string | ResourcePath $dst Path of destiny. 
     * @param arr $options Options to save the image.
     * 
     * Defaul Options.
     * [
     *   'deleteTemp' => true
     * ]
     */
    public function save($src, $dst = null, $options = [])
    {
        if ($src instanceof ResourcePath) {
            if ($src->config['resource']['isTemp']) {
                $dst = str_replace('_' . $this->prefixTemp, '', $dst->path);
            }
        }

        $options = ArrayHelper::merge([
                    'deleteTemp' => true,
                        ], $options);

        if ($this->serverType == ResourceImage::SERVER_LOCAL) {
            $this->copy($src, $dst);
            if ($options['deleteTemp']) {
                $this->delete($src);
            }
        }

        /**
         * @todo: implement in mode s3.
         */
    }

    /**
     * Upload a file.
     * 
     * @param string | ResourcePath $dst Path of destiny. 
     * @param UploadedFile $file File.
     * 
     * @return bool True if the file was uploaded correctly False otherwise; 
     * 
     */
    public function upload($dst, $file)
    {

        $dst = $dst instanceof ResourcePath ? $dst->path : $dst;

        try {
            $file->saveAs($dst);
        } catch (Exception $ex) {
            Yii::error($ex);
            return false;
        }

        return true;
    }

    /**
     * Save a copy of file.
     * 
     * @param string $src | ResourcePath Path of origin. 
     * @param string $dst | ResourcePath Path of destiny. 
     * 
     * @return bool True if the file was copied correctly False otherwise; 
     */
    public function copy($src, $dst)
    {

        $src = $src instanceof ResourcePath ? $src->path : $src;
        $dst = $dst instanceof ResourcePath ? $dst->path : $dst;

        try {
            copy($src, $dst);
        } catch (Exception $ex) {
            Yii::error($ex);
            return false;
        }

        return true;
    }

    /**
     * Delete file.
     * @param string | ResourcePath $src File of delete. 
     */
    public function delete($src)
    {

        $src = $src instanceof ResourcePath ? $src->path : $src;

        if (file_exists($src)) {
            unlink($src);
        }

        /**
         * @todo: implement in mode s3.
         */
    }

    /**
     * Generate the thumbnail.
     * 
     * @param string $src | ResourcePath Path of origin. 
     * @param arr $options Options to generate thumbnail.
     * 
     * @return bool True if the file was generated correctly False otherwise; 
     * Default Options.
     * [
     *   'width' => 200,
     *   'height' => 200,
     *   'mode' => ManipulatorInterface::THUMBNAIL_INSET
     * ]
     * 
     */
    public function thumbnail($src, $options = [])
    {

        $src = $src instanceof ResourcePath ? $src->path : $src;
        $options = ArrayHelper::merge([
                    'width' => 200,
                    'height' => 200,
                    'mode' => ManipulatorInterface::THUMBNAIL_INSET
                        ], $options);

        try {

            Image::thumbnail($src, $options['width'], $options['height'], $options['mode'])
                    ->save($src);
        } catch (Exception $ex) {
            Yii::error($ex);
            return false;
        }

        return true;
    }

    /**
     * Generate Crop.
     * 
     * @param string $src | ResourcePath Path of origin. 
     * @param arr $options Options to generate crop.
     * 
     * @return bool True if the file was generated correctly False otherwise; 
     * Default Options.
     * [
     *   'width' => 200,
     *   'height' => 200,
     *   'points' => [0, 0],
     *   'box' => [200, 200]
     * ]
     * 
     */
    public function crop($src = null, $options = [])
    {

        $src = $src instanceof ResourcePath ? $src->path : $src;

        $options = ArrayHelper::merge([
                    'width' => 200,
                    'height' => 200,
                    'points' => [0, 0],
                    'box' => [200, 200],
                        ], $options);

        try {

            Image::crop($src, $options['width'], $options['height'], $options['points'])
                    ->resize(new Box($options['box'][0], $options['box'][1]))
                    ->save($src);
        } catch (Exception $ex) {
            Yii::error($ex);
            return false;
        }

        return true;
    }

    /**
     * Resources common override in each module.
     * @return arr
     */
    public function resources()
    {
        return [];
    }

    /**
     * Url to images by default.
     * @return []
     */
    public function resourcesDefault()
    {
        return [];
    }

    /**
     * Sizes of the system.
     * @return string
     */
    public function getSizes()
    {
        return [
            self::SIZE_ORIGINAL => '',
            self::SIZE_THUMB => 'thumb'
        ];
    }

    /**
     * Assign config of each part of path.
     * @param arr $options Configs of each part of path.
     */
    public function setConfigPartsToPath($options)
    {

        $this->setConfigRoot(!empty($options['root']) ? $options['root'] : []);
        $this->setConfigBasePath(!empty($options['basePath']) ? $options['basePath'] : []);
        $this->setConfigResource(!empty($options['resource']) ? $options['resource'] : []);
        $this->setConfigSize(!empty($options['size']) ? $options['size'] : []);
        $this->setConfigName(!empty($options['name']) ? $options['name'] : []);
    }

    /**
     * Generate url from configuration.
     */
    public function generateUrl()
    {
        return implode(DIRECTORY_SEPARATOR, array_filter([
            $this->partPathRoot,
            $this->partPathBasePath,
            $this->partPathResource,
            $this->partPathSize,
            $this->partPathName
                        ], "strlen"));
    }

    /*
     * Configs to part of path root.
     * @param arr $options Options to generate part.
     */

    public function setConfigRoot($options)
    {

        $options = ArrayHelper::merge([
                    'isWebUrl' => true
                        ], $options);

        $this->configUrl['root'] = $options;

        $root = $options['isWebUrl'] ? $this->cdn : $this->getAbsoluteDirectory();

        $this->setPartPath('root', $root);
    }

    /*
     * Configs to part of path basePath.
     * @param arr $options Options to generate part.
     */

    public function setConfigBasePath($options)
    {

        $options = ArrayHelper::merge([
                    'enviroment' => $this->getEnviromentRunning()
                        ], $options);

        $this->configUrl['basePath'] = $options;

        $basePath = $this->getBasePath($options['enviroment']);

        $this->setPartPath('basePath', $basePath);
    }

    /*
     * Configs to part of path resource.
     * @param arr $options Options to generate part.
     */

    public function setConfigResource($options)
    {

        $options = ArrayHelper::merge([
                    'type' => '',
                    'isTemp' => false
                        ], $options);

        $this->configUrl['resource'] = $options;

        $resource = $this->getResource($options['type']);

        if ($options['isTemp']) {
            $resource .= '_temp';
        }

        $this->setPartPath('resource', $resource);
    }

    /*
     * Configs to part of path size.
     * @param arr $options Options to generate part.
     */

    public function setConfigSize($options)
    {

        $options = ArrayHelper::merge([
                    'type' => self::SIZE_ORIGINAL
                        ], $options);

        $this->configUrl['size'] = $options;

        $this->setPartPath('size', $this->getSize($options['type']));
    }

    /*
     * Configs to part of path name.
     * @param arr $options Options to generate part.
     */

    public function setConfigName($options)
    {

        $options = ArrayHelper::merge([
                    'title' => '', 
                    'ext' => '', 
                    'concatTime' => true
                        ], $options);

        $this->configUrl['name'] = $options;

        $name = $options['title'];

        if (empty($name)) {
            $name = $this->getDefaultNameImageResource();
        }

        if ($options['concatTime']) {
            $name = $name . $this->symbolConcatTime . time() . "." . $options['ext'];
        }

        $this->setPartPath('name', $name);
    }

    /**
     * Get the enviroment running in the real time.
     * @return string
     */
    public function getEnviromentRunning()
    {
        return YII_ENV;
    }

    /**
     * Get the directory absolute depending of
     * enviroment.
     * @return type
     */
    public function getAbsoluteDirectory()
    {
        return Yii::getAlias($this->containerBasePath);
    }

    /**
     * Get the size specified.
     * @param string $size Type of size;
     * @return string
     */
    public function getSize($size)
    {

        $sizes = $this->getSizes();

        if (!empty($sizes[$size])) {
            return $sizes[$size];
        }
    }

    /**
     * Get the resource specified.
     * @param string $type Type of resource.
     */
    public function getResource($type)
    {

        $resources = $this->resources();

        if (!empty($resources[$type])) {
            return $resources[$type];
        }
    }

    /**
     * Get base path configured.
     * @param string $enviroment Enviroment running.
     * @return string
     */
    public function getBasePath($enviroment = null)
    {
        if (!empty($this->basePaths[$enviroment])) {
            return $this->basePaths[$enviroment];
        }
    }

    /**
     * Get name of the default image of resource.
     * @param string $type Type of resource.
     * @return string
     */
    public function getDefaultNameImageResource($resourceType = null)
    {

        $type = empty($resourceType) ? $this->configUrl['resource']['type'] : $resourceType;

        $resources = $this->resourcesDefault($type);

        if (!empty($resources[$type])) {
            return $resources[$type];
        }
    }

    /**
     * Assign the part of path specified.
     * @param string $partPath Name of part path 'resource, name, size, root, basePath'
     * @param string $value Value to set in part path.
     */
    public function setPartPath($partPath, $value)
    {
        $property = 'partPath' . ucwords($partPath);
        $this->$property = $value;
    }

    /**
     * Get the part of path specified.
     * @param string $partPath Name of part path;
     * @return string Value of that part path.
     */
    public function getPartPath($partPath)
    {
        $property = 'partPath' . $partPath;
        return $this->$property;
    }

    /**
     * Get object instance of model
     * @param string $name
     * @param array $config
     * @return ActiveRecord
     */
    public function model($name)
    {
        $className = $this->modelClasses[ucfirst($name)];
        return Yii::createObject(array_merge(['class' => $className]));
    }

}
