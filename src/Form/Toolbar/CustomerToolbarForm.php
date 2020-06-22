<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\CustomerQuery;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering the customer.
 */
class CustomerToolbarForm extends AbstractToolbarForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addSearchTermInputField($builder);
        $this->addVisibilityChoice($builder);
        $this->addPageSizeChoice($builder);
        $this->addHiddenPagination($builder);
        $this->addHiddenOrder($builder);
        $this->addHiddenOrderBy($builder, CustomerQuery::CUSTOMER_ORDER_ALLOWED);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CustomerQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
