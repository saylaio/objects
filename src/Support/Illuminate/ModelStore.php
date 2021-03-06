<?php

namespace Sayla\Objects\Support\Illuminate;

use Illuminate\Database\Eloquent\Model;
use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\DataModel;


class ModelStore implements ObjectStore
{
    protected $useTransactions = false;
    /** @var Model */
    protected $model;

    public function __toString(): string
    {
        return $this->toStoreString();
    }

    public function create(DataModel $object): iterable
    {
        if ($this->useTransactions) {
            return $this->getConnection()->transaction(function () use ($object) {
                return $this->createModel($object);
            });
        }
        return $this->createModel($object);
    }

    public function delete(DataModel $object): iterable
    {
        $model = $this->findModel($object->getKey());
        if ($this->useTransactions) {
            return $this->getConnection()->transaction(function () use ($model, $object) {
                return $this->deleteModel($model, $object);
            });
        }
        return $this->deleteModel($model, $object);
    }

    public function toStoreString(): string
    {
        return 'Eloquent[' . get_class($this->model) . ']';
    }

    public function update(DataModel $object): iterable
    {
        $model = $this->findModel($object->getKey());
        if ($this->useTransactions) {
            return $this->getConnection()->transaction(function () use ($model, $object) {
                return $this->updateModel($model, $object);
            });
        }
        return $this->updateModel($model, $object);
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection()
    {
        return $this->model->getConnection();
    }

    /**
     * @param Model $model
     * @return Model
     */
    protected function createModel($object)
    {
        $dataType = $object->dataType();
        $data = $dataType->extractMappable($object);
        $model = $this->model->newInstance($data);
        $model->save();
        return $dataType->hydrateData($model->getAttributes());
    }

    /**
     * @param $key
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function findModel($key)
    {
        return $this->model->newQuery()->findOrFail($key);
    }

    /**
     * @param Model $model
     * @param DataModel $object
     * @return Model
     */
    protected function deleteModel($model, $object)
    {
        $model->delete();
        return $object->dataType()->hydrateData($model->getAttributes());
    }

    /**
     * @param Model $model
     * @param DataModel $object
     * @return Model
     */
    protected function updateModel($model, $object)
    {
        $dataType = $object->dataType();
        $data = $dataType->extractData($object);
        $model->fill($data);
        $model->save();
        return $dataType->hydrateData($model->getAttributes());
    }

    /**
     * @param bool $useTransactions
     * @return $this
     */
    public function setUseTransactions(bool $useTransactions)
    {
        $this->useTransactions = $useTransactions;
        return $this;
    }

    /**
     * @return bool
     */
    public function usesTransactions(): bool
    {
        return $this->useTransactions;
    }
}