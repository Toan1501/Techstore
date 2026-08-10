<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'first_options' => [
                    'constraints' => [
                        new NotBlank(
                            message: 'Vui lòng nhập mật khẩu mới.',
                        ),
                        new Length(
                            min: 8,
                            minMessage: 'Mật khẩu phải có ít nhất {{ limit }} ký tự.',
                            // max length allowed by Symfony for security reasons
                            max: 4096,
                        ),
                    ],
                    'label' => 'Mật khẩu mới',
                ],
                'second_options' => [
                    'label' => 'Nhập lại mật khẩu',
                ],
                'invalid_message' => 'Mật khẩu nhập lại không khớp.',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
