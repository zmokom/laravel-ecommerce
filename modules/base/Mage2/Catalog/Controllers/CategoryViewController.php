<?php
/**
 * Mage2
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ind.purvesh@gmail.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://mage2.website for more information.
 *
 * @author    Purvesh <ind.purvesh@gmail.com>
 * @copyright 2016-2017 Mage2
 * @license   https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License v3.0
 */
namespace Mage2\Catalog\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Mage2\Catalog\Models\Category;
use Mage2\Catalog\Models\Product;
use Mage2\Catalog\Models\ProductAttribute;
use Mage2\Catalog\Models\ProductVarcharValue;
use Mage2\System\Models\Configuration;
use Mage2\Framework\System\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CategoryViewController extends Controller
{

    public function view(Request $request, $slug)
    {

        $productsOnCategoryPage = Configuration::getConfiguration('mage2_catalog_no_of_product_category_page');
        $category = Category::where('slug', '=', $slug)->get()->first();

        $facadeDB = DB::table('products')->select('products.id');

        $i = 0;
        $facadeDB->join('category_product', 'category_product.product_id', 'products.id');

        foreach ($request->all() as $attrSlug => $value) {

            if ($i == 0) {
                $facadeDB->join('product_varchar_values', 'products.id', 'product_varchar_values.product_id');
            }
            $attribute = ProductAttribute::where('identifier', '=', $attrSlug)->first();

            $facadeDB->orWhere(function ($query) use ($attribute, $value) {
                $query->where('product_varchar_values.product_attribute_id', '=', $attribute->id)
                    ->where('product_varchar_values.value', '=', $value);
            });

            $i++;
        }

        $facadeDB->where('category_product.category_id', '=', $category->id);
        $dbProducts = $facadeDB->paginate($productsOnCategoryPage);

        $products = Collection::make([]);
        foreach ($dbProducts as $product) {
            $products->push(Product::find($product->id));
        }

        $collections = new Paginator($products, $productsOnCategoryPage, $dbProducts->currentPage());

        return view('catalog.category.view')
            ->with('category', $category)
            ->with('params' , $request->all())
            ->with('products', $collections);
    }
}
