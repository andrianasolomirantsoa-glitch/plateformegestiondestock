<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Famille;
use App\Repository\CategorieRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FamilleType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la famille',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Exemple : Boissons gazeuses',
                ],
            ])

            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'query_builder' => function (
                    CategorieRepository $repository
                ) {
                    return $repository->createQueryBuilder('c')
                        ->andWhere('c.actif = :actif')
                        ->setParameter('actif', true)
                        ->orderBy('c.nom', 'ASC');
                },
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionner une catégorie',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Description de la famille...',
                ],
            ])

            ->add('actif', CheckboxType::class, [
                'label' => 'Famille active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => Famille::class,
        ]);
    }
}
