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
        $productAttributes = [];

        foreach ($product->attributes as $name => $val) {
            foreach ($attributes as $attribute) {
                if ($attribute->code != $name)
                    continue;

                foreach ($attribute->values as $attVal) {
                    foreach (explode(',', $val) as $v) {
                        if ($v == $attVal->code) {
                            if ($attribute->name == 'Category') {
                                $productAttributes[] = [
                                    'name' => $attribute->name,
                                    'value' => $this->getCategoryValue($attVal->code, $attribute->values)
                                ];
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

        return $productAttributes;
    }

    /**
     * @param string $category
     * @param array $categories
     * @return string
     */
    private function getCategoryValue(string $category, array $categories): string
    {
        $codes = explode('_', substr($category, 4));

        if (count($codes) == 1) {
            return $categories[array_search("cat_{$codes[0]}", array_column($categories, 'cat'))]->name;
        }

        return $categories[array_search("cat_{$codes[0]}", array_column($categories, 'code'))]->name . ' > '
            . $categories[array_search("cat_{$codes[0]}_{$codes[1]}", array_column($categories, 'code'))]->name;
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
