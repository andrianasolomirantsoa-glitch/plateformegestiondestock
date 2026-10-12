<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;

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

            /*
             * =====================================================
             * PHOTO PRINCIPALE
             * =====================================================
             */
            ->add('imageFile', FileType::class, [
                'label' => 'Photo principale',
                'mapped' => false,
                'required' => false,
                'help' => 'JPG, JPEG, PNG ou WEBP. Taille maximale : 5 Mo.',
                'constraints' => [
                    new Image(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Veuillez sélectionner une image JPG, PNG ou WEBP valide.',
                    ),
                ],
            ])

            /*
             * =====================================================
             * PHOTOS SUPPLÉMENTAIRES
             * =====================================================
             */
            ->add('imageFiles', FileType::class, [
                'label' => 'Photos supplémentaires',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'help' => 'Vous pouvez sélectionner plusieurs images. JPG, JPEG, PNG ou WEBP. 5 Mo maximum par image.',
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                ],
                'constraints' => [
                    new All([
                        new Image(
                            maxSize: '5M',
                            mimeTypes: [
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ],
                            mimeTypesMessage: 'Chaque fichier doit être une image JPG, PNG ou WEBP valide.',
                        ),
                    ]),
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

