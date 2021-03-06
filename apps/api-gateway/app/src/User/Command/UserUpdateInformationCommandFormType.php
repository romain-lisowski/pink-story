<?php

declare(strict_types=1);

namespace App\User\Command;

use App\Form\AbstractFormType;
use App\User\Model\UserGender;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserUpdateInformationCommandFormType extends AbstractFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('gender', ChoiceType::class, [
                'choices' => UserGender::getChoices(),
            ])
            ->add('language_id', TextType::class, [
                'property_path' => 'languageId',
            ])
            ->add('reading_language_ids', CollectionType::class, [
                'property_path' => 'readingLanguageIds',
                'entry_type' => TextType::class,
                'required' => false,
                'allow_add' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'method' => Request::METHOD_PATCH,
        ]);
    }
}
