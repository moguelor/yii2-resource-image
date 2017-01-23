<?php

namespace jmoguelruiz\yii2\components\resourceimage\models;

use Imagine\Image\Box;
use Imagine\Image\ManipulatorInterface;
use jmoguelruiz\yii2\components\resourceimage\ResourceImage;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\imagine\Image;
use yii\web\UploadedFile;

class ResourcePath extends Model
{

    /**
     * Final path.
     * @var string 
     */
    public $path;

    /**
     * Configuration for final path.
     * @var arr 
     */
    public $config = [];

    /**
     * Server type.
     */
    public $serverType = null;

    /**
     * Prefix temp.
     */
    public $prefixTemp = "";
    
    /**
     * Save the file in non-temp path.
     * 
     * @param string $dst Path of destiny. If this is null will work with path of the instance, replacing _temp.
     * @param arr $options Options to save the image.
     * 
     * Defaul Options.
     * [
     *   'deleteTemp' => true
     * ]
     */
    public function save($dst = null, $options = [])
    {

        $dst = !empty($dst) ? $dst : str_replace('_' . $this->prefixTemp, '', $this->path);

        $options = ArrayHelper::merge([
                    'deleteTemp' => true,
                        ], $options);
        
        if ($this->serverType == ResourceImage::SERVER_LOCAL) {
            $this->copyTo($dst);
            if ($options['deleteTemp']) {
                $this->delete($this->path);
            }
        }

        /**
         * @todo: implement in mode s3.
         */
    }
    
    /**
     * Upload a file.
     * 
     * @param UploadedFile $file File.
     * @param string $dst Path of destiny. If this is null will work with path of the instance.
     * 
     * @return bool True if the file was uploaded correctly False otherwise; 
     * 
     */
    public function upload($file, $dst = null)
    {

        $dst = !empty($dst) ? $dst : $this->path;

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
     * @param string $dst Path of destiny. If this is null will work with path of the instance.
     * @param string $src Path of origin. If this is null will work with path of the instance.
     * 
     * @return bool True if the file was copied correctly False otherwise; 
     */
    public function copyTo($dst = null, $src = null)
    {

        $src = !empty($src) ? $src : $this->path;
        $dst = !empty($dst) ? $dst : $this->path;

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
     * @param string $path File of delete. If this is null will work with path of the instance.
     */
    public function delete($path = null)
    {

        $path = !empty($path) ? $path : $this->path;

        if (file_exists($path)) {
            unlink($path);
        }

        /**
         * @todo: implement in mode s3.
         */
    }

    /**
     * Generate the thumbnail.
     * 
     * @param arr $options Options to generate thumbnail.
     * @param string $dst Path of destiny. If this is null will work with path of the instance.
     * @param string $src Path of origin. If this is null will work with path of the instance.
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
    public function thumbnail($options = [], $dst = null, $src = null)
    {

        $src = !empty($src) ? $src : $this->path;
        $dst = !empty($dst) ? $dst : $this->path;

        $options = ArrayHelper::merge([
                    'width' => 200,
                    'height' => 200,
                    'mode' => ManipulatorInterface::THUMBNAIL_INSET
                        ], $options);

        try {

            Image::thumbnail($src, $options['width'], $options['height'], $options['mode'])
                    ->save($dst);
        } catch (Exception $ex) {
            Yii::error($ex);
            return false;
        }

        return true;
    }

    /**
     * Generate Crop.
     * 
     * @param arr $options Options to generate crop.
     * @param string $dst Path of destiny. If this is null will work with path of the instance.
     * @param string $src Path of origin. If this is null will work with path of the instance.
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
    public function crop($options = [], $dst = null, $src = null)
    {

        $src = !empty($src) ? $src : $this->path;
        $dst = !empty($dst) ? $dst : $this->path;
        $options = ArrayHelper::merge([
                    'width' => 200,
                    'height' => 200,
                    'points' => [0, 0],
                    'box' => [200, 200],
                        ], $options);

        try {

            Image::crop($src, $options['width'], $options['height'], $options['points'])
                    ->resize(new Box($options['box'][0], $options['box'][1]))
                    ->save($dst);
        } catch (Exception $ex) {
            Yii::error($ex);
            return false;
        }

        return true;
    }
        

}
