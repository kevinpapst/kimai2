<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\Toolbar;

use AppBundle\Repository\Query\UserQuery;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering the user.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class UserToolbarForm extends VisibilityToolbarForm
{

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
