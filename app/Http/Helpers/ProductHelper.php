<?php


namespace App\Http\Helpers;


class ProductHelper
{
    private $productsUrl = 'https://draft.grebban.com/backend/products.json';
    private $metaUrl = 'https://draft.grebban.com/backend/attribute_meta.json';

    /**
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getPage(int $page = 1, int $size = 10): array
    {
        $products = $this->parseProducts();
        usort($products, $this->build_sorter('name'));

        return [
            'page' => $page,
            'totalPages' => round(count($products) / $size) ?: 1,
            'products' => array_slice($products, $size * ($page - 1), $size)
        ];
    }

    /**
     * @return array
     */
    private function parseProducts(): array
    {
        $products = $this->getJson($this->productsUrl);
        $attributes = $this->getJson($this->metaUrl);
        $parsed = [];

        foreach ($products as $product) {
            $parsed[] = [
                'id' => $product->id,
                'name' => $product->name,
                'attributes' => $this->parseProductAttributes($product, $attributes),
            ];
        }

        return $parsed;
    }

    /**
     * @param object $product
     * @param array $attributes
     * @return array
     */
    private function parseProductAttributes(object $product, array $attributes): array
    {
        $categories = '';
        $productAttributes = [];

        foreach ($product->attributes as $name => $val) {
            foreach ($attributes as $attribute) {
                if ($attribute->code != $name)
                    continue;

                foreach ($attribute->values as $attVal) {
                    foreach (explode(',', $val) as $v) {
                        if ($v == $attVal->code) {
                            if ($attribute->name == 'Category') {
                                $categories = empty($categories)
                                    ? $attVal->name
                                    : "{$categories} > {$attVal->name}";
                            } else {
                                $productAttributes[] = [
                                    'name' => $attribute->name,
                                    'value' => $attVal->name
                                ];
                            }
                        }
                    }
                }

                break;
            }
        }

        $productAttributes[] = [
            'name' => 'Category',
            'value' => $categories
        ];

        return $productAttributes;
    }

    /**
     * @param string $url
     * @return array
     */
    private function getJson(string $url): array
    {
        return json_decode(file_get_contents($url));
    }

    /**
     * Used for sorting a multidimensional array
     * by a given key
     *
     * @param $key
     * @return \Closure
     */
    function build_sorter($key) {
        return function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        };
    }
}
