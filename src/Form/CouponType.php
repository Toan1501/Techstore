<?php

namespace App\Form;

use App\Entity\Coupon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CouponType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, ['label' => 'Mã (VD: GIAM10)'])
            ->add('type', ChoiceType::class, [
                'label' => 'Loại giảm',
                'choices' => ['Theo phần trăm (%)' => 'percent', 'Số tiền cố định (₫)' => 'fixed'],
            ])
            ->add('value', NumberType::class, ['label' => 'Giá trị (10 = 10% hoặc 10₫)'])
            ->add('minOrder', NumberType::class, ['label' => 'Đơn tối thiểu (₫)', 'required' => false, 'empty_data' => '0'])
            ->add('maxUses', IntegerType::class, ['label' => 'Giới hạn lượt dùng (để trống = không giới hạn)', 'required' => false, 'attr' => ['min' => 1]])
            ->add('active', CheckboxType::class, ['label' => 'Kích hoạt', 'required' => false])
            ->add('expiresAt', DateTimeType::class, ['label' => 'Hết hạn (để trống = vô hạn)', 'required' => false, 'widget' => 'single_text'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Coupon::class]);
    }
}
