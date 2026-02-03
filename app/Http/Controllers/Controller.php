<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Inventory API Documentation",
 *      description="API documentation for the Inventory project",
 *      @OA\Contact(
 *          email="your-email@example.com"
 *      ),
 *      @OA\License(
 *          name="Apache 2.0",
 *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Inventory API Server"
 * )
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer"
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="Product model",
 *     @OA\Property(property="id_product", type="string", format="uuid", description="Primary key"),
 *     @OA\Property(property="nom", type="string", description="Product name"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Product description"),
 *     @OA\Property(property="quantite_stock", type="integer", description="Current stock quantity"),
 *     @OA\Property(property="quantite_min_alerte", type="integer", description="Minimum stock alert threshold"),
 *     @OA\Property(property="reference", type="string", nullable=true, description="Product reference code"),
 *     @OA\Property(property="is_direction", type="boolean", description="Is the product for direction only"),
 *     @OA\Property(property="agence", type="string", nullable=true, description="Agency associated with the product"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
 * )
 *
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
