<?php

namespace Micayael\AdminLteMakerBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Common\Inflector\Inflector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Routing\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;

class MakeCrud extends AbstractMaker
{
    private $doctrineHelper;

    private $formTypeRenderer;

    private $bundleConfig;

    public function __construct(DoctrineHelper $doctrineHelper, FormTypeRenderer $formTypeRenderer, array $bundleConfig)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->formTypeRenderer = $formTypeRenderer;
        $this->bundleConfig = $bundleConfig;
    }

    public static function getCommandName(): string
    {
        return 'make:app:crud';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates CRUD for Doctrine entity class')
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create CRUD (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            ->setHelp('')
        ;

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($entities);

            $value = $io->askQuestion($question);

            $input->setArgument('entity-class', $value);
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Route::class,
            'router'
        );

        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );

        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig-bundle'
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm-pack'
        );

        $dependencies->addClassDependency(
            CsrfTokenManager::class,
            'security-csrf'
        );

        $dependencies->addClassDependency(
            ParamConverter::class,
            'annotations'
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists($input->getArgument('entity-class'), $this->doctrineHelper->getEntitiesForAutocomplete()),
            'Entity\\'
        );

        $entityDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($entityClassDetails->getFullName());

        $repositoryVars = [];

        if (null !== $entityDoctrineDetails->getRepositoryClass()) {
            $repositoryClassDetails = $generator->createClassNameDetails(
                '\\'.$entityDoctrineDetails->getRepositoryClass(),
                'Repository\\',
                'Repository'
            );

            $repositoryVars = [
                'repository_full_class_name' => $repositoryClassDetails->getFullName(),
                'repository_class_name' => $repositoryClassDetails->getShortName(),
                'repository_var' => lcfirst(Inflector::singularize($repositoryClassDetails->getShortName())),
            ];
        }

        // Form

        $iter = 0;
        do {
            $formClassDetails = $generator->createClassNameDetails(
                $entityClassDetails->getRelativeNameWithoutSuffix().($iter ?: '').'Type',
                'Form\\',
                'Type'
            );
            ++$iter;
        } while (class_exists($formClassDetails->getFullName()));

        $this->formTypeRenderer->render(
            $formClassDetails,
            $entityDoctrineDetails->getFormFields(),
            $entityClassDetails
        );

        // Controllers

        $controllers = [
            'Index',
            'New',
            'Show',
            'Edit',
            'Delete',
        ];

        $entityVarPlural = lcfirst(Inflector::pluralize($entityClassDetails->getShortName()));
        $question = new Question('$entityVarPlural', $entityVarPlural);
        $entityVarPlural = $io->askQuestion($question);

        $entityVarSingular = lcfirst($entityClassDetails->getShortName());
        $question = new Question('$entityVarSingular', $entityVarSingular);
        $entityVarSingular = $io->askQuestion($question);

        //--------------------------------------------------------------------------------------------------------------

        $entityTwigVarPlural = Str::asTwigVariable($entityVarPlural);
        $question = new Question('$entityTwigVarPlural', $entityTwigVarPlural);
        $entityTwigVarPlural = $io->askQuestion($question);

        $entityTwigVarSingular = Str::asTwigVariable($entityVarSingular);
        $question = new Question('$entityTwigVarSingular', $entityTwigVarSingular);
        $entityTwigVarSingular = $io->askQuestion($question);

        //--------------------------------------------------------------------------------------------------------------

        $routeName = $entityTwigVarSingular;
        $question = new Question('$routeName', $routeName);
        $routeName = $io->askQuestion($question);

        $urlContext = $this->bundleConfig['url_context'];
        $question = new Question('$urlContext', $urlContext);
        $urlContext = $io->askQuestion($question);

        //--------------------------------------------------------------------------------------------------------------

        $templatesPath = $this->bundleConfig['template_base_path'].$entityTwigVarSingular;
        $question = new Question('$templatesPath', $templatesPath);
        $templatesPath = $io->askQuestion($question);

        //--------------------------------------------------------------------------------------------------------------

        $controllerNamespace = $this->bundleConfig['controller_base_namespace'].$entityClassDetails->getRelativeNameWithoutSuffix().'\\';
        $question = new Question('$controllerNamespace', $controllerNamespace);
        $controllerNamespace = $io->askQuestion($question);

        if ('\\' !== substr($controllerNamespace, strlen($controllerNamespace) - 1, 1)) {
            $controllerNamespace .= '\\';
        }
        //--------------------------------------------------------------------------------------------------------------

        foreach ($controllers as $controller) {
            $controllerCapitalize = ucfirst($controller);

            $controllerClassDetails = $generator->createClassNameDetails(
                $controllerCapitalize.'Controller',
                $controllerNamespace,
                'Controller'
            );

            $generator->generateController(
                $controllerClassDetails->getFullName(),
                __DIR__.'/../Resources/skeleton/crud/controller/'.$controllerCapitalize.'Controller.tpl.php',
                array_merge([
                    'entity_full_class_name' => $entityClassDetails->getFullName(),
                    'entity_class_name' => $entityClassDetails->getShortName(),
                    'entity_class_name_upper' => strtoupper($entityClassDetails->getShortName()),
                    'form_full_class_name' => $formClassDetails->getFullName(),
                    'form_class_name' => $formClassDetails->getShortName(),
                    'route_path' => Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix()),
                    'route_name' => $routeName,
                    'templates_path' => $templatesPath,
                    'entity_var_plural' => $entityVarPlural,
                    'entity_twig_var_plural' => $entityTwigVarPlural,
                    'entity_var_singular' => $entityVarSingular,
                    'entity_twig_var_singular' => $entityTwigVarSingular,
                    'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                ],
                    $repositoryVars
                )
            );
        }

        // Templates

        $templates = [
            '_delete_form' => [
                'route_name' => $routeName,
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_class_name_upper' => strtoupper($entityClassDetails->getShortName()),
            ],
            '_show_data' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_class_name_plural' => ucfirst($entityVarPlural),
                'entity_class_name_upper' => strtoupper($entityClassDetails->getShortName()),
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => $routeName,
                'templatesPath' => $templatesPath,
            ],
            'edit' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_class_name_plural' => ucfirst($entityVarPlural),
                'entity_class_name_upper' => strtoupper($entityClassDetails->getShortName()),
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'route_name' => $routeName,
            ],
            'index' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_class_name_plural' => ucfirst($entityVarPlural),
                'entity_class_name_upper' => strtoupper($entityClassDetails->getShortName()),
                'entity_twig_var_plural' => $entityTwigVarPlural,
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => $routeName,
            ],
            'new' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_class_name_plural' => ucfirst($entityVarPlural),
                'entity_class_name_upper' => strtoupper($entityClassDetails->getShortName()),
                'route_name' => $routeName,
            ],
            'show' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_class_name_plural' => ucfirst($entityVarPlural),
                'entity_class_name_upper' => strtoupper($entityClassDetails->getShortName()),
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => $routeName,
                'templatesPath' => $templatesPath,
            ],
            'delete' => [
                'entity_class_name' => $entityClassDetails->getShortName(),
                'entity_class_name_plural' => ucfirst($entityVarPlural),
                'entity_class_name_upper' => strtoupper($entityClassDetails->getShortName()),
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier' => $entityDoctrineDetails->getIdentifier(),
                'entity_fields' => $entityDoctrineDetails->getDisplayFields(),
                'route_name' => $routeName,
                'templatesPath' => $templatesPath,
            ],
        ];

        foreach ($templates as $template => $variables) {
            $generator->generateTemplate(
                $templatesPath.'/'.$template.'.html.twig',
                __DIR__.'/../Resources/skeleton/crud/templates/'.$template.'.tpl.php',
                $variables
            );
        }

        // Routes

        $generator->generateFile(
            'config/routes/'.$routeName.'.yaml',
            __DIR__.'/../Resources/skeleton/crud/routes.tpl.php',
            [
                'route_name' => $routeName,
                'entity_class_name' => $entityClassDetails->getRelativeNameWithoutSuffix(),
                'url_context' => $urlContext,
                'controller_base_namespace' => substr($controllerNamespace, 0, strlen($controllerNamespace) - 1),
            ]
        );

        // Actions View Helper
        $generator->generateClass(
            'App\\Twig\\ViewHelper\\Action\\'.$entityClassDetails->getRelativeNameWithoutSuffix().'ActionsViewHelper',
            __DIR__.'/../Resources/skeleton/crud/ActionsViewHelper.tpl.php',
            [
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_class_name_upper' => strtoupper($entityClassDetails->getShortName()),
                'route_name' => $routeName,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text(sprintf('Next: Check your new CRUD by going to <fg=yellow>%s/</>', $urlContext.$routeName));
    }
}
