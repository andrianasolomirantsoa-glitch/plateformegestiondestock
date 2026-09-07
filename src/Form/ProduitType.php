<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProduitType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'attr' => [
                    'placeholder' => 'Ex : PRD-001',
                ],
            ])

            ->add('nom', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'placeholder' => 'Nom du produit',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Description du produit...',
                ],
            ])

            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionner une catégorie',
            ])

            ->add('prixAchat', MoneyType::class, [
                'label' => 'Prix d’achat',
                'currency' => 'MGA',
                'divisor' => 1,
                'attr' => [
                    'placeholder' => '0',
                ],
            ])

            ->add('prixVente', MoneyType::class, [
                'label' => 'Prix de vente',
                'currency' => 'MGA',
                'divisor' => 1,
                'attr' => [
                    'placeholder' => '0',
                ],
            ])

            ->add('quantiteStock', IntegerType::class, [
                'label' => 'Quantité en stock',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0',
                ],
            ])

            ->add('seuilAlerte', IntegerType::class, [
                'label' => 'Seuil d’alerte',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0',
                ],
            ])

            ->add('unite', TextType::class, [
                'label' => 'Unité',
                'attr' => [
                    'placeholder' => 'Ex : pièce, kg, litre',
                ],
            ])

            ->add('actif', CheckboxType::class, [
                'label' => 'Produit actif',
                'required' => false,
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}

