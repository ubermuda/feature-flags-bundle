<?php

namespace Ubermuda\FeatureFlagsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType as FeatureFlagTypeEnum;

/** @extends AbstractType<FeatureFlagRequest> */
class FeatureFlagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name', 'attr' => ['autofocus' => true]])
            ->add('type', EnumType::class, [
                'class' => FeatureFlagTypeEnum::class,
                'label' => 'Type',
                'attr' => [
                    'data-feature-flag-form-target' => 'typeSelect',
                    'data-action' => 'change->feature-flag-form#updateType',
                ],
            ])
            ->add('boolValue', CheckboxType::class, [
                'label' => 'Value',
                'required' => false,
                'row_attr' => ['data-feature-flag-form-target' => 'boolField'],
            ])
            ->add('intValue', IntegerType::class, [
                'label' => 'Value',
                'required' => false,
                'row_attr' => ['data-feature-flag-form-target' => 'intField'],
            ])
            ->add('options', TextareaType::class, [
                'label' => 'Options (one per line)',
                'required' => false,
                'row_attr' => ['data-feature-flag-form-target' => 'optionsField'],
                'attr' => ['rows' => 5],
            ])
            ->add('tags', HiddenType::class, ['required' => false])
            ->add('save', SubmitType::class, ['label' => 'Save'])
        ;

        $builder->get('options')
            ->addModelTransformer(new CallbackTransformer(
                fn (array $opts): string => implode("\n", $opts),
                static fn (?string $text): array => self::parseOptionsTextarea($text ?? ''),
            ));

        $builder->get('tags')
            ->addModelTransformer(new CallbackTransformer(
                fn (array $tags): string => json_encode($tags, JSON_THROW_ON_ERROR),
                fn (string $json): array => '' !== $json ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : [],
            ));

        // selectValue is a ChoiceType whose choice list comes from `options`. Choices must be
        // known on initial render (PRE_SET_DATA, from the request DTO) and on submit
        // (PRE_SUBMIT, from raw posted textarea text — the transformer hasn't run yet).
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $data = $event->getData();
            $opts = $data instanceof FeatureFlagRequest ? $data->options : [];
            self::addSelectValueField($event->getForm(), $opts);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $raw = $event->getData();
            $text = is_array($raw) && isset($raw['options']) && is_string($raw['options']) ? $raw['options'] : '';
            self::addSelectValueField($event->getForm(), self::parseOptionsTextarea($text));
        });
    }

    /**
     * @param FormInterface<FeatureFlagRequest> $form
     * @param list<string>                      $choices
     */
    private static function addSelectValueField(FormInterface $form, array $choices): void
    {
        $form->add('selectValue', ChoiceType::class, [
            'label' => 'Value',
            'required' => false,
            'placeholder' => [] === $choices ? 'Add options first…' : false,
            'choices' => [] === $choices ? [] : array_combine($choices, $choices),
            'row_attr' => ['data-feature-flag-form-target' => 'selectField'],
        ]);
    }

    /** @return list<string> */
    private static function parseOptionsTextarea(string $raw): array
    {
        if ('' === $raw) {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return array_values(array_filter(array_map(trim(...), $lines), static fn (string $line): bool => '' !== $line));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => FeatureFlagRequest::class]);
    }
}
