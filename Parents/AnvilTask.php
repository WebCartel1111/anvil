<?php

namespace App\Containers\Vendor\Anvil\Parents;

use Apiato\Core\Exceptions\CoreInternalErrorException;
use App\Ship\Exceptions\DeleteResourceFailedException;
use App\Ship\Exceptions\NotFoundException;
use App\Ship\Parents\Repositories\Repository;
use App\Ship\Parents\Tasks\Task;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class AnvilTask extends Task
{
    protected Repository $repository;

    /**
     * @throws CoreInternalErrorException
     * @throws RepositoryException
     */
    public function runFetchAll()
    {
        return $this->repository->addRequestCriteria()->paginate();
    }

    /**
     * @throws NotFoundException
     */
    public function runFetch($id, $relations)
    {
        try {
            $entity = $this->repository->find($id);

            if ($relations !== []) {
                $entity->load($relations);
            }

            return $entity;
        } catch(\Throwable $e) {
            throw new NotFoundException();
        }
    }

    /**
     * @throws ValidatorException
     */
    public function runCreate(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * @throws ValidatorException
     */
    public function runUpdate($id, array $data)
    {
        return $this->repository->update($data, $id);
    }

    /**
     * @throws DeleteResourceFailedException
     */
    public function runDelete($id): bool
    {
        return $this->repository->delete($id);
    }
}