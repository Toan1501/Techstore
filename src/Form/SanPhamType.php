<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\SanPham;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;


class SanPhamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Tên sản phẩm'])
            ->add('price', NumberType::class, ['label' => 'Giá bán (₫)'])
            ->add('priceOld', NumberType::class, ['label' => 'Giá gốc (để trống nếu không giảm giá)', 'required' => false])
            ->add('stock', IntegerType::class, [
                'label' => 'Số lượng tồn kho',
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('cate', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Danh mục',
                'placeholder' => '-- Chọn danh mục --',
                'required' => false,
            ])
            ->add('brand', TextType::class, ['required' => false, 'label' => 'Thương hiệu'])
            ->add('sku', TextType::class, ['required' => false, 'label' => 'Mã SKU'])
            ->add('warranty', TextType::class, ['required' => false, 'label' => 'Bảo hành (vd: 24 tháng)'])
            ->add('description', TextareaType::class, [
                'label' => 'Mô tả',
                'required' => false,
            ])
            ->add('specifications', TextareaType::class, [
                'required' => false,
                'label' => 'Thông số kỹ thuật (mỗi dòng "Tên: Giá trị")',
                'attr' => ['rows' => 6, 'placeholder' => "CPU: Intel Core i5\nRAM: 16GB\nỔ cứng: 512GB SSD"],
            ])
            ->add('photo', FileType::class, ['label' => 'Ảnh chính (png, jpeg)', 'data_class' => null, 'required' => false])
            ->add('galleryImages', FileType::class, [
                'label' => 'Ảnh gallery (không bắt buộc, chọn nhiều)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SanPham::class,
        ]);
    }
}
