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
    public function getPage(int $page = 1, int $size = 10)
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
        $products = $this->getProducts();
        $attributes = $this->getAttributeMeta();
        $parsed = [];

        foreach ($products as $product) {
            $productAttributes = [];
            $categories = '';

            foreach ($product->attributes as $name => $val) {
                $val = explode(',', $val);

                foreach ($attributes as $attribute) {
                    if ($attribute->code != $name)
                        continue;

                    foreach ($attribute->values as $attVal) {
                        foreach ($val as $v) {
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

            $parsed[] = [
                'id' => $product->id,
                'name' => $product->name,
                'attributes' => $productAttributes,
            ];
        }

        return $parsed;
    }

    /**
     * @return array
     */
    private function getProducts(): array
    {
        return json_decode(file_get_contents($this->productsUrl));
    }

    /**
     * @return array
     */
    private function getAttributeMeta(): array
    {
        return json_decode(file_get_contents($this->metaUrl));
    }

    function build_sorter($key) {
        return function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        };
    }
}
