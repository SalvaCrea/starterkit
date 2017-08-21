<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wizaplace\Catalog\CatalogService;
use Wizaplace\Catalog\Review\ReviewService;
use Wizaplace\Exception\NotFound;
use Wizaplace\Seo\SeoService;
use Wizaplace\Seo\SlugTargetType;
use Wizaplace\User\UserService;

class SearchController extends Controller
{
    public function searchAction(Request $request): Response
    {
        $catalogService = $this->get(CatalogService::class);
        $selectedCategoryId = (int) $request->get("selected_category_id");
        $selectedCategory = $selectedCategoryId ? $catalogService->getCategory($selectedCategoryId) : null;

        $filters = [];
        if ($selectedCategoryId) {
            $filters['categories'] = $selectedCategoryId;
        }

        return $this->render('search/search.html.twig', [
            'searchQuery' => $request->query->get('q'),
            'filters' => $filters,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function attributeVariantAction(string $slug): Response
    {
        $seoService = $this->get(SeoService::class);
        $slugTarget = $seoService->resolveSlug($slug);
        if (!$slugTarget || $slugTarget->getObjectType() != SlugTargetType::ATTRIBUTE_VARIANT()) {
            throw $this->createNotFoundException('Variant '.$slug.' not found');
        }
        $selectedVariantId = $slugTarget->getObjectId();

        $catalogService = $this->get(CatalogService::class);
        $selectedVariant = $catalogService->getAttributeVariant((int) $selectedVariantId);

        $filters = [];
        $filters[$selectedVariant->getAttributeId()] = $selectedVariantId;

        return $this->render('search/variant-attribute.html.twig', [
            'filters' => $filters,
            'selectedVariant' => $selectedVariant,
        ]);
    }

    public function companyAction(string $slug): Response
    {
        $seoService = $this->get(SeoService::class);
        $slugTarget = $seoService->resolveSlug($slug);

        if (is_null($slugTarget) || $slugTarget->getObjectType() != SlugTargetType::COMPANY()) {
            throw $this->createNotFoundException("Company '${slug}' Not Found'");
        }
        $companyId = (int) $slugTarget->getObjectId();

        $company = $this->get(CatalogService::class)->getCompanyById($companyId);
        $filters = [];
        $filters['companies'] = $companyId;

        $reviewService = $this->get(ReviewService::class);
        $reviews = $reviewService->getCompanyReviews($companyId);
        $canUserReviewCompany = $reviewService->canUserReviewCompany($companyId);

        return $this->render('search/company.html.twig', [
            'filters' => $filters,
            'company' => $company,
            'reviews' => $reviews,
            'canUserReviewCompany' => $canUserReviewCompany,
        ]);
    }
}
