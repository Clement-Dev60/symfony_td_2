<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\SecurityBundle\Security;

class EmailVerificationListener
{
    public function __construct(
        private Security $security,
        private RouterInterface $router
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $allowedRoutes = [
            'app_home',
            'app_logout',
            'app_login',
            'app_verify_email',
            'app_profile',
            'app_resend_verification',
        ];

        $currentRoute = $event->getRequest()->attributes->get('_route');

        if (!$user->isVerified() && !in_array($currentRoute, $allowedRoutes)) {
            $event->setResponse(new RedirectResponse(
                $this->router->generate('app_profile')
            ));
        }
    }
}