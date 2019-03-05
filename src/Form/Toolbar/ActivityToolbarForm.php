<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\ActivityQuery;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering the activities.
 */
class ActivityToolbarForm extends AbstractToolbarForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addPageSizeChoice($builder);
        $this->addVisibilityChoice($builder);
        $builder->add('globalsOnly', ChoiceType::class, [
            'choices' => [
                'yes' => 1,
                'no' => 0,
            ],
            'placeholder' => null,
            'required' => false,
            'label' => 'label.globalsOnly',
        ]);
        $this->addCustomerChoice($builder);
        $this->addProjectChoice($builder);
        $this->addHiddenPagination($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ActivityQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
