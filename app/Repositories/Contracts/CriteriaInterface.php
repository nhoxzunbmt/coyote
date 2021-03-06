<?php

namespace Coyote\Repositories\Contracts;

use Coyote\Repositories\Criteria\Criteria;

/**
 * Interface CriteriaInterface
 */
interface CriteriaInterface
{
    /**
     * @return mixed
     */
    public function getCriteria();

    /**
     * @param Criteria $criteria
     * @return $this
     */
    public function getByCriteria(Criteria $criteria);

    /**
     * @param Criteria $criteria
     * @return $this
     */
    public function pushCriteria(Criteria $criteria);

    /**
     * @return $this
     */
    public function applyCriteria();
}
