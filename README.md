### Welcome to the yii2-resource-image wiki!

This is a component for yii2, to manage the images to upload in server and the generation of paths easiest.

# Override

I think that the url path is formed for five parts: **root, base, resource, size, name**. For example:

* **root** : http://project-template.dev
* **basePath** : images/dev
* **resource** : player
* **size** : thumb
* **name** : image_1485386176.jpg

And all togueter is http://project-template.dev/images/dev/player/thumb/image_1485386176.jpg

For that reason, I built this component to configure each part and generate the url most easy, I included many functions that help you to manage the images files.

Each part has its own configuration.

## Root 

This part is for root of url, the follow code is the default configuration:

```php
[
  'isWebUrl' => true
]
```
When `isWebUrl` is true, generate `http://project-template.dev/` otherwise is `/Users/josemoguel/Documents/fuentes/project-template/frontend/web`

## Basepath

This part is automatically configured from environment that you are running.

```php
[
 'enviroment' => $this->getEnviromentRunning()
]
```
You can set the environment if you want, there are three environments by default `dev,test,prod`.

## Resource

The resource that belongs to the image, player, user, gamer for example.

```php
[
    'type' => '',
    'isTemp' => false
]
```

When `isTemp` is `true`, add to the resource the prefix **_temp** or if you have one configured `player_temp`, set `isTemp = true` for process the image, and later save in the real directory.

## Size

You can have many sizes of the image to save.

```php
[
  'type' => self::SIZE_ORIGINAL
]
```

## Name

You can assign the name of image.
```php
[
   'title' => '', 
   'ext' => '', 
   'concatTime' => true
]
```

If you put `concatTime = true` will concat the time in the name image resolving the problem with cache. `image_1485386176.jpg`.


# Installation

The way prefer is composer:

`composer install jmoguelruiz/yii2-resource-image`

Or adding in you composer file.

`"jmoguelruiz/yii2-resource-image": "1.0.*"`

In config/main inside of component put the follow code:

```php
[
   'components' => [
      'resourceImage' => [
                'class' => 'common\components\ResourceImage',
                // Optional Configs
                'modelClasses' => [ // Custom models.
                    'ResourcePath' => 'common\components\ResourcePath'
                ],
                'basePaths' => [ // Base paths of your project in each enviroment.
                    'prod' => 'images/',
                    'test' => 'images/test',
                    'dev' => 'images/dev'
                ],
                'serverType' => ResourceImage::SERVER_LOCAL // Server type for now is only saved in same server.
                'prefixTemp' => 'temp', // Temp prefix for folders.
                'containerBasePath' => "@frontend/web", // Container base to save images.
                'cdn' => 'http://www.project.com', // If you want cdn.
                'symbolConcatTime' => '_' // Symbol to concat the name with the string time.
            ],
  ...
 ]
]
```

You should add the file ResourceImage in your common\components folder, overriding this extension, for configure the follow options.

```php
<?php

namespace common\components;

class ResourceImage extends \jmoguelruiz\yii2\components\ResourceImage
{
    /**
     * Your types of resources
     */
    const TYPE_ONE = 'one';
    const TYPE_TWO = 'two';
    const TYPE_THREE = 'three';
    
    /**
     * Your sizes 
     */
    const SIZE_CROP = 'crop';
    const SIZE_250_250 = '250_250';
    const SIZE_200_200 = '200_200';
    
    /**
     * You resource path
     */
    public function resources()
    {
        return \yii\helpers\ArrayHelper::merge(parent::resources(),[
          self::TYPE_ONE => 'one',
          self::TYPE_TWO => 'two',
          self::TYPE_THREE => 'two' .  DIRECTORY_SEPARATOR . 'three' //  two/three
        ]);
    }
    
    /**
     * The images names to default resource.
     */
    public function resourcesDefault(){
        
        return \yii\helpers\ArrayHelper::merge(parent::resources(),[
          self::TYPE_ONE => 'default.jpg',
          self::TYPE_TWO => 'default.jpg',
          self::TYPE_THREE => 'default.jpg'
        ]);
        
    }
    
    /**
     * You can override the sizes for customize your size in your
     * project, the name of size will be the name of the new folder.
     * Example: 
     * 
     * image/player <- Here save the original
     * image/player/thumb <- Here save the size thumb
     * image/player/250_250 <- Here save your custom size.
     * 
     * @return type
     */
    public function getSizes()
    {
        return \yii\helpers\ArrayHelper::merge([
            self::SIZE_CROP => 'crop',
            self::SIZE_250_250 => '250_250',
            self::SIZE_200_200 => '200_200'
        ],parent::getSizes());
    }
    
}

```

Create the folders where the images will be saved and configuring your enviroments `enviroment/index.php` for permissions.

```php
    'Development' => [
        'path' => 'dev',
        'setWritable' => [
            'backend/runtime',
            'backend/web/assets',
            'frontend/runtime',
            'frontend/web/assets',
            'frontend/web/images/one/,  <--- Example
            'frontend/web/images/one_temp/, <--- Example
            'frontend/web/images/one/250_250, <--- Example
        ],
        'setExecutable' => [
            'yii',
            'yii_test',
        ],
        'setCookieValidationKey' => [
            'backend/config/main-local.php',
            'frontend/config/main-local.php',
        ],
    ],
```

# Usage

### Generating a url.

```php
$resourceImage = Yii::$app->resourceImage;

// This function return a model ResourcePath.
$pathOne = $resourceImage->newPath([
    'root' => ['isWebUrl' => false],
    'basePath' => ['enviroment' => 'dev'],
    'resource' => ['type' => ResourceImage::TYPE_PLAYER],
    'size' => ['type' => ResourceImage::SIZE_THUMB],
    'name' => ['title' => 'imageOne', 'ext' => 'jpg', 'concatTime' => true]
]);

/*
 Result:
 /Users/josemoguel/Documents/fuentes/plantillas/project-template/frontend/web/images/dev/player/thumb/image_1485389319.jpg
*/ 
$pathOne->path;

```

### Saving one image file.

```php
$file = UploadedFile::getInstance($user, 'file');
```

```php
// Generating the path to save the image.
// /Users/josemoguel/Documents/fuentes/plantillas/project-template/frontend/web/images/dev/player_temp/12321_1485389719.jpg
$imageOriginalTemp = $resourceImage->newPath([
 'root' => ['isWebUrl' => false],
 'resource' => ['type' => ResourceImage::TYPE_PLAYER, 'isTemp' => true],
 'name' => ['title' => '12321', 'ext' => $file->extension]
]);

//Uploading the image.
$resourceImage->upload($imageOriginalTemp, $file);
// Saving image, automatically detect if directory is temp, after remove the postFix _temp and save in the real path.
// player_temp -> player
$resourceImage->save($imageOriginalTemp);
```

### Save image in many sizes.

```php
  $imageOriginalTemp = $resourceImage->newPath([
       'root' => ['isWebUrl' => false],
       'resource' => ['type' => ResourceImage::TYPE_PLAYER, 'isTemp' => true],
       'name' => ['title' => '12321', 'ext' => $file->extension]
  ]);
            
  $imageThumbnailTemp = $resourceImage->newPath(ArrayHelper::merge($imageOriginalTemp->config,[
        'size' => ['type' => ResourceImage::SIZE_THUMB]
  ]));
            
   $imageCropTemp = $resourceImage->newPath(ArrayHelper::merge($imageOriginalTemp->config,[
         'size' => ['type' => ResourceImage::SIZE_CROP]
   ]));
            
   $resourceImage->upload($imageOriginalTemp, $file);
   $resourceImage->copy($imageOriginalTemp, $imageThumbnailTemp);
   $resourceImage->copy($imageOriginalTemp, $imageCropTemp);
   $resourceImage->save($imageOriginalTemp);
            
   $resourceImage->thumbnail($imageThumbnailTemp);
   $resourceImage->save($imageThumbnailTemp);
            
   $resourceImage->crop($imageCropTemp);
   $resourceImage->save($imageCropTemp);
```

### Save images for sizes.

```php
  $imageOriginalTemp = $resourceImage->newPath([
       'root' => ['isWebUrl' => false],
       'resource' => ['type' => ResourceImage::TYPE_PLAYER, 'isTemp' => true],
       'name' => ['title' => '12321', 'ext' => $file->extension]
  ]);

  $resourceImage->upload($imageOriginalTemp, $file);
  // You can proccess the image with some functions in the componente, thumbnail, crop.
  $resourceImage->saveBySize(ResourceImage::SIZE_THUMB, $imageOriginalTemp, ['thumbnail']);
```

### Get web url

```php
// http://project-template.dev/images/dev/player/thumb/jijo.jpg
$resourceImage->getWebUrl("jijo.jpg", ResourceImage::TYPE_PLAYER, ResourceImage::SIZE_THUMB);

```

### Get default web url

```php
//http://project-template.dev/images/dev/player/crop/default.jpg
$resourceImage->getDefaultWebUrl(ResourceImage::TYPE_PLAYER, ResourceImage::SIZE_CROP);
```

## ResourcePath

Generating new path you can access the data of the model.

```php 
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
     * Name generated
     * @var string 
     */
    public $name;
    
    /**
     * Size generated
     * @var string 
     */
    public $size;
    
    /**
     * Resource generated
     * @var string
     */
    public $resource;
    
    /**
     * Base Path generated
     * @var string 
     */
    public $basePath;
    
    /**
     * Root generated
     * @var string 
     */
    public $root;
    
}
```

# Contributions

You can use this component if you want can contribute send email to jmoguelruiz@gmail.com.

_**From comunity to comunity...**_