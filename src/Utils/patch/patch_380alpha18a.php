<?php

namespace App\Utils\patch;




/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use App\Utils\base;
use App\Utils\patchAbstract;


use Symfony\Component\Process\ExecutableFinder;

class patch_380alpha18a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.18';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $app['conf']->set(['binaries', 'recess_binary'], (new ExecutableFinder())->find('recess'));

        return true;
    }
}
