<?php

namespace App\Form;

use App\Entity\Order;
use App\Service\ShippingCalculator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer_name', TextType::class, ['label' => 'Họ và tên'])
            ->add('customer_email', EmailType::class, ['label' => 'Email'])
            ->add('customer_phone', TextType::class, ['label' => 'Số điện thoại'])
            ->add('shipping_province', ChoiceType::class, [
                'label' => 'Tỉnh / Thành phố',
                'placeholder' => '-- Chọn Tỉnh / Thành phố --',
                'choices' => array_combine(ShippingCalculator::PROVINCES, ShippingCalculator::PROVINCES),
            ])
            ->add('shipping_district', TextType::class, ['label' => 'Quận / Huyện'])
            ->add('shipping_ward', TextType::class, ['label' => 'Phường / Xã'])
            ->add('customer_address', TextType::class, ['label' => 'Địa chỉ (số nhà, tên đường)'])
            ->add('payment_method', ChoiceType::class, [
                'label' => 'Phương thức thanh toán',
                'expanded' => true,
                'choices' => [
                    'Thanh toán khi nhận hàng (COD)' => 'cod',
                    'Chuyển khoản ngân hàng' => 'bank_transfer',
                    'Thanh toán online qua VNPay' => 'vnpay',
                ],
                'data' => 'cod',
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Ghi chú đơn hàng (không bắt buộc)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
