<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types = 1);

namespace AppBundle\Routing;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Wizaplace\Seo\SeoService;
use Wizaplace\Seo\SlugTargetType;

/**
 * A wrapper for the Symfony router.
 * It matches slugs to objects' ids when necessary.
 */
class SlugRouterWrapper implements RouterInterface, RequestMatcherInterface
{
    /** @var RouterInterface|RequestMatcherInterface */
    private $wrappedRouter;

    /** @var SeoService */
    private $seoService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(RouterInterface $wrappedRouter, SeoService $seoService, LoggerInterface $logger)
    {
        $this->wrappedRouter = $wrappedRouter;
        $this->seoService = $seoService;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function matchRequest(Request $request): array
    {
        try {
            return $this->wrappedRouter->matchRequest($request);
        } catch(ResourceNotFoundException $exception) {
            if ($request->get('resolve_slug', false) && $this->processSlug($request)) {
                return $this->wrappedRouter->matchRequest($request);
            } else {
                throw $exception;
            }
        }
    }

    /**
     * @return bool true if a valid slug was found, false otherwise
     */
    private function processSlug(Request $request): bool
    {
        $uri = $request->getUri();

        // Split by / and take the last item
        $uri = array_filter(explode('/', $uri));
        if (empty($uri)) {
            return false;
        }
        $uri = end($uri);

        $slugTarget = $this->seoService->resolveSlug($uri); // @TODO: caching
        if (null === $slugTarget) {
            return false;
        }

        switch ($slugTarget->getObjectType()) {
            case SlugTargetType::PRODUCT():
                $request->attributes->set('productId', $slugTarget->getObjectId());
                break;
            case SlugTargetType::CATEGORY():
                $request->attributes->set('categoryId', $slugTarget->getObjectId());
                break;
            case SlugTargetType::COMPANY():
                $request->attributes->set('companyId', $slugTarget->getObjectId());
                break;
            case SlugTargetType::ATTRIBUTE_VARIANT():
                $request->attributes->set('variantId', $slugTarget->getObjectId());
                break;
            case SlugTargetType::CMS_PAGE():
                $request->attributes->set('pageId', $slugTarget->getObjectId());
                break;
            default:
                $this->logger->error("Unknown SlugTargetType {$slugTarget->getObjectType()}");
                return false;
        }

        return true;
    }

    public function getRouteCollection()
    {
        return $this->wrappedRouter->getRouteCollection();
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->wrappedRouter->generate($name, $parameters, $referenceType);
    }

    public function setContext(RequestContext $context)
    {
        return $this->wrappedRouter->setContext($context);
    }

    public function getContext()
    {
        return $this->wrappedRouter->getContext();
    }

    public function match($pathinfo)
    {
        return $this->wrappedRouter->match($pathinfo);
    }
}
