<?php

namespace Ubermuda\FeatureFlagsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Minimal confirmation form: a single submit plus the framework's CSRF token.
 * Bundle-local so the admin does not depend on the host app's delete form.
 *
 * @extends AbstractType<null>
 */
class ConfirmDeleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('confirm', SubmitType::class, ['label' => 'Delete']);
    }
}
