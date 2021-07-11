<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\API\BaseApiController;
use App\Utils\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Custom form field type to display the date-time input fields.
 */
class DateTimeChoicePickerType extends AbstractType
{
    private $localeSettings;

    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $dateTimePicker = $this->localeSettings->getDateTimePickerFormat();
        $dateTimeFormat = $this->localeSettings->getDateTimeTypeFormat();

        $resolver->setDefaults([
            'documentation' => [
                'type' => 'string',
                'format' => 'date-time',
                'example' => (new \DateTime())->format(BaseApiController::DATE_FORMAT_PHP),
            ],
            'label' => 'label.begin',
            'compound' => true,
            'widget' => 'single_text',
            'html5' => false,
            'format' => $dateTimeFormat,
            'format_picker' => $dateTimePicker,
            'with_seconds' => false,
            'time_increment' => 1,
            'model_timezone' => null,
            'view_timezone' => null
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dateTimePicker = $this->localeSettings->getDateTimePickerFormat();
        $dateTimeFormat = $this->localeSettings->getDateTimeTypeFormat();

        $builder
            ->add('addressLine1', DateType::class, [
                'help' => 'Street address, P.O. box, company name',
            ])
        ;
    }

    
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-datetimepicker' => 'on',
            'autocomplete' => 'off',
            'placeholder' => strtoupper($options['format']),
            'data-format' => $options['format_picker'],
            'data-time-picker-increment' => $options['time_increment'],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateTimeType::class;
    }
}
