<?php

namespace App\Form;

use App\Entity\StoreSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('storeName', TextType::class, ['label' => 'Tên cửa hàng'])
            ->add('hotline', TextType::class, ['label' => 'Hotline', 'required' => false])
            ->add('email', TextType::class, ['label' => 'Email', 'required' => false])
            ->add('address', TextType::class, ['label' => 'Địa chỉ', 'required' => false])
            ->add('bankName', TextType::class, ['label' => 'Ngân hàng', 'required' => false])
            ->add('bankAccount', TextType::class, ['label' => 'Số tài khoản', 'required' => false])
            ->add('bankOwner', TextType::class, ['label' => 'Chủ tài khoản', 'required' => false])
            ->add('freeShipThreshold', IntegerType::class, ['label' => 'Miễn phí ship từ (₫)'])
            ->add('shipFeeInner', IntegerType::class, ['label' => 'Phí ship nội thành lớn (₫)'])
            ->add('shipFeeOuter', IntegerType::class, ['label' => 'Phí ship tỉnh khác (₫)'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => StoreSettings::class]);
    }
}
