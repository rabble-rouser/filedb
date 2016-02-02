<?php

namespace RabbleRouser\FileDB\Utils;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class CacheResource
{
    public $cachePath           = '/';
    public $resourceName        = '';
    public $transformer;
    protected $transformerClass;
    protected $filesystem;
    protected $index            = 'id';
    protected $extension        = 'json';

    public function __construct()
    {
        // Ensure that our extension starts with a '.'.
        $this->extension = (strpos($this->extension, '.', 0) === 0) ? $this->extension : '.' . $this->extension;

        $this->cachePath = app()->storagePath() . '/app/cache' . '/' . $this->resourceName;

        $this->filesystem = new Filesystem(new Local($this->cachePath));

        $this->transformer = new $this->transformerClass;
    }

    public function index()
    {
        $responseArray = array();
        foreach($this->filesystem->listContents() as $file) {
            $responseArray[$file['filename']] = $this->filesystem->read($file['path']);
        }

        return $responseArray;
    }

    public function get($name)
    {
        $standard_response = array();
        if($this->filesystem->has($name . '.json')) {
            $result = $this->filesystem->read($name . '.json');
            $standard_response = json_decode($result);
        }else {
            // @TODO: Handle no result response. - Chad
            $standard_response["response"] = "No results could be found for " . $name . ".";
        }

        return $standard_response;
    }

    public function store($entity)
    {
        $jsonEntity = json_decode($entity);

        $index  = $this->index;
        $key    = $jsonEntity->$index;
        $path   = $key . $this->extension;

        // Write our new dataset to the filesystem. If a dataset
        // already exists for this index, we'll overwrite it.
        $this->filesystem->put($path, json_encode($jsonEntity));
        // @TODO: Handle errors with put writes. - Chad

        return $key;
    }

    public function delete($entityIndex)
    {
        $entityFilename = $entityIndex . $this->extension;

        if($this->filesystem->has($entityFilename)) {
            $this->filesystem->delete($entityFilename);

            return array('response' => 'The entity for index ' . $entityIndex . ' was deleted successfully.', 'status' => 'success');
        } else {
            return array('response' => 'No entity could be found at index ' . $entityIndex .'.', 'status' => 'incomplete');
        }

    }
}