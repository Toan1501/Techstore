<?php

namespace App\Form;

use App\Entity\Banner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BannerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Tiêu đề', 'required' => false])
            ->add('link', UrlType::class, ['label' => 'Liên kết (URL, không bắt buộc)', 'required' => false, 'default_protocol' => null])
            ->add('position', IntegerType::class, ['label' => 'Thứ tự hiển thị', 'required' => false, 'empty_data' => '0'])
            ->add('active', CheckboxType::class, ['label' => 'Hiển thị trên trang chủ', 'required' => false])
            ->add('imageFile', FileType::class, ['label' => 'Ảnh banner', 'mapped' => false, 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Banner::class]);
    }
}
