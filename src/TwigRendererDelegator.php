<?php
/**
 * @copyright: DotKernel
 * @library: dotkernel/dot-twigrenderer
 * @author: n3vrax
 * Date: 9/23/2016
 * Time: 8:20 PM
 */

namespace Dot\Twig;


use Dot\Authentication\AuthenticationInterface;
use Dot\Authorization\AuthorizationInterface;
use Dot\FlashMessenger\View\FlashMessengerRenderer;
use Dot\Navigation\View\NavigationRenderer;
use Dot\Twig\Extension\AuthenticationExtension;
use Dot\Twig\Extension\AuthorizationExtension;
use Dot\Twig\Extension\FlashMessengerExtension;
use Dot\Twig\Extension\NavigationExtension;
use Interop\Container\ContainerInterface;
use Zend\Expressive\Twig\TwigRenderer;
use Zend\View\HelperPluginManager;
use Zend\View\Renderer\PhpRenderer;

/**
 * Class TwigRendererDelegator
 * @package Dot\Twig
 */
class TwigRendererDelegator
{
    /**
     * @param ContainerInterface $container
     * @param $name
     * @param callable $callback
     * @param array|null $options
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $renderer = call_user_func($callback);
        if($renderer instanceof TwigRenderer) {
            $reflectionClass = new \ReflectionClass(TwigRenderer::class);
            $reflectionProperty = $reflectionClass->getProperty('template');
            $reflectionProperty->setAccessible(true);

            /** @var \Twig_Environment $twig */
            $environment = $reflectionProperty->getValue($renderer);

            //add the zend view helpers to twig
            /** @var HelperPluginManager $viewHelperManager */
            $viewHelperManager = $container->get('ViewHelperManager');
            $zfRenderer = new PhpRenderer();
            $zfRenderer->setHelperPluginManager($viewHelperManager);
            $environment->registerUndefinedFunctionCallback(
                function($name) use ($viewHelperManager, $zfRenderer) {
                    if(!$viewHelperManager->has($name)) {
                        return false;
                    }

                    $callable = [$zfRenderer->plugin($name), '__invoke'];
                    $options  = ['is_safe' => ['html']];
                    return new \Twig_SimpleFunction(null, $callable, $options);
                }
            );

            //add our default extensions, if dependencies are present
            if($container->has(AuthenticationInterface::class)) {
                $environment->addExtension($container->get(AuthenticationExtension::class));
            }

            if($container->has(AuthorizationInterface::class)) {
                $environment->addExtension($container->get(AuthorizationExtension::class));
            }

            if($container->has(NavigationRenderer::class)) {
                $environment->addExtension($container->get(NavigationExtension::class));
            }

            if($container->has(FlashMessengerRenderer::class)) {
                $environment->addExtension($container->get(FlashMessengerExtension::class));
            }
        }
    }
}