<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('image',FileType::class,[
                'label' => 'Project Image',
                'required' => false,
                'mapped' => false
            ])
            ->add('filename')
            ->add('numberOfTasks',IntegerType::class)
            ->add('description',TextareaType::class)
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'In progress' => 'inProgress',
                    'Done' => 'done',
                    'Blocked' => 'blocked',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
