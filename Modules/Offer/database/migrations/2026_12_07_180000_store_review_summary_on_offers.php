<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La note moyenne et le nombre d'avis enregistrés sur l'offre, tenus à jour par ses avis (décision
 * 2026-11-25-accueil-a-grande-echelle) : plus de moyenne calculée à chaque liste. Un index pour trier les offres d'un
 * pays par note (« À la une ») et un sur la date de publication (les tris par date, les carrousels). Les offres
 * existantes reçoivent leur note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->decimal('reviews_avg_rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->index(['country_id', 'status', 'reviews_avg_rating']);
            $table->index('published_at');
        });

        foreach (DB::table('offer_reviews')->select('offer_id', DB::raw('avg(rating) as average'), DB::raw('count(*) as total'))->groupBy('offer_id')->get() as $summary) {
            DB::table('offers')->where('id', $summary->offer_id)->update([
                'reviews_avg_rating' => round((float) $summary->average, 2),
                'reviews_count' => (int) $summary->total,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['country_id', 'status', 'reviews_avg_rating']);
            $table->dropIndex(['published_at']);
            $table->dropColumn(['reviews_avg_rating', 'reviews_count']);
        });
    }
};
