<?php

namespace jmoguelruiz\yii2\components;

use yii\base\Model;

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
     * Name
     * @var string 
     */
    public $name;
    
    /**
     * Size
     * @var string 
     */
    public $size;
    
    /**
     * Resource
     * @var string
     */
    public $resource;
    
    /**
     * Base Path
     * @var string 
     */
    public $basePath;
    
    /**
     * Root
     * @var string 
     */
    public $root;
    
}