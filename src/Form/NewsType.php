<?php

namespace App\Form;

use App\Entity\News;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Tiêu đề'])
            ->add('slug', TextType::class, ['label' => 'Slug (để trống sẽ tự tạo)', 'required' => false])
            ->add('excerpt', TextareaType::class, ['label' => 'Tóm tắt', 'required' => false, 'attr' => ['rows' => 2]])
            ->add('content', TextareaType::class, ['label' => 'Nội dung (hỗ trợ HTML)', 'required' => false, 'attr' => ['rows' => 10]])
            ->add('imageFile', FileType::class, ['label' => 'Ảnh đại diện', 'mapped' => false, 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => News::class]);
    }
}
