<?php

namespace Modules\Auth\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Database\Factories\UserFactory;
use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Enums\IdentityDocumentType;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Notifications\Channels\SmsChannel;
use Modules\Auth\Services\AvatarService;
use Modules\Auth\Services\IdentityDocumentService;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\NotificationSettings;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'first_name', 'last_name', 'gender', 'birth_date', 'country_id', 'region_id', 'description', 'email', 'phone', 'phone_country_id', 'locale', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasLocalePreference, HasMedia, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes;

    /**
     * Un compte supprimé, même de façon récupérable, emporte ses jetons d'API :
     * Sanctum ne les supprime pas lui-même (relation polymorphe, sans clé
     * étrangère). Sa photo, elle, ne part qu'avec la purge définitive.
     */
    protected static function booted(): void
    {
        static::deleting(fn (User $user) => $user->tokens()->delete());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'gender' => GenderEnum::class,
            'birth_date' => 'date',
            'phone_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'identified_at' => 'datetime',
            'identity_document_type' => IdentityDocumentType::class,
            'notification_settings' => 'array',
        ];
    }

    /** Vu dans ces dernières minutes : en ligne. */
    public const ONLINE_MINUTES = 5;

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /** Pays qui a délivré sa pièce d'identité (décision 2026-11-27-identite-pays-et-type-de-piece). @return BelongsTo<Country, $this> */
    public function identityCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'identity_country_id');
    }

    /** Pays de l'indicatif du téléphone. @return BelongsTo<Country, $this> */
    public function phoneCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'phone_country_id');
    }

    /**
     * Ses adresses, chacune avec son destinataire, dans l'ordre de création ;
     * l'adresse elle-même peut être partagée avec d'autres comptes (décision
     * 2026-11-18-adresse-du-compte-modele).
     *
     * @return HasMany<UserAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class)->orderBy('id');
    }

    /**
     * Son adresse de résidence : une de ses adresses, marquée, une seule (décision 2026-11-27-adresse-de-residence).
     *
     * @return HasOne<UserAddress, $this>
     */
    public function residence(): HasOne
    {
        return $this->hasOne(UserAddress::class)->where('is_residence', true);
    }

    /**
     * En ligne : vu par l'API dans les `ONLINE_MINUTES` dernières minutes.
     */
    protected function isOnline(): Attribute
    {
        return Attribute::get(fn (): bool => $this->last_seen_at !== null
            && $this->last_seen_at->gte(now()->subMinutes(self::ONLINE_MINUTES)));
    }

    /**
     * « Prénom Nom », pour l'affichage.
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * Photo de profil : un seul fichier (une nouvelle photo remplace l'ancienne),
     * déjà optimisé en WebP par `AvatarService`. Supprimer le compte la supprime.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(AvatarService::COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['image/webp'])
            ->useDisk('public');

        // Les documents d'identité, privés, que l'équipe regarde au back-office (décision 2026-11-25-identification).
        $this->addMediaCollection(IdentityDocumentService::COLLECTION)
            ->acceptsMimeTypes(IdentityDocumentService::acceptedMimeTypes())
            ->useDisk(IdentityDocumentService::DISK);
    }

    /** L'équipe a vérifié ses documents et rempli sa date d'identification (décision 2026-11-25-identification). */
    public function isIdentified(): bool
    {
        return $this->identified_at !== null;
    }

    /**
     * Une conversion par endroit où la photo s'affiche (`AvatarService::conversions()`, décision
     * 2026-11-24-avatar-photos-offre-conversions) : un carré recadré au centre, produit tout de suite (aucune file
     * d'attente ne tourne).
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (AvatarService::conversions() as $name => $size) {
            $this->addMediaConversion((string) $name)
                ->performOnCollections(AvatarService::COLLECTION)
                ->fit(Fit::Crop, (int) $size, (int) $size)
                ->format('webp')
                ->quality(AvatarService::quality())
                ->nonOptimized()
                ->nonQueued();
        }
    }

    /**
     * L'adresse de la photo de profil ; avec `$conversion` (`list`, `detail`), celle de sa
     * conversion, ou celle de la photo tant que la conversion n'est pas produite ; `null` sans photo.
     */
    public function avatarUrl(?string $conversion = null): ?string
    {
        $media = $this->getFirstMedia(AvatarService::COLLECTION);
        if ($media === null) {
            return null;
        }
        if ($conversion === null) {
            return $media->getUrl();
        }
        if (! $media->hasGeneratedConversion($conversion)) {
            return $media->getUrl();
        }

        return $media->getUrl($conversion);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatarUrl('list');
    }

    /**
     * Initiales pour l'avatar : première lettre du prénom et du nom, sinon du
     * pseudonyme ; en majuscules et sans accent (« Élodie Ängel » → « EA »).
     */
    protected function initials(): Attribute
    {
        return Attribute::get(function (): string {
            $letters = collect([$this->first_name, $this->last_name])
                ->map(fn (?string $part): string => mb_substr(trim((string) $part), 0, 1))
                ->implode('');

            if ($letters === '') {
                $letters = mb_substr(trim((string) $this->name), 0, 1);
            }

            return Str::upper(Str::ascii($letters));
        });
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * Les canaux sur lesquels le compte reçoit les notifications (décision
     * 2026-11-19-notifications-reservations) : l'e-mail et le SMS ouverts par
     * l'application (`auth.channels`), si son adresse ou son numéro est vérifié, et
     * si le compte les a gardés pour « Activité du compte ».
     *
     * @return list<string>
     */
    public function notificationChannels(): array
    {
        $available = [
            NotificationSettings::EMAIL => AuthChannels::email() && $this->email !== null && $this->email_verified_at !== null ? 'mail' : null,
            NotificationSettings::SMS => AuthChannels::phone() && $this->phone !== null && $this->phone_verified_at !== null ? SmsChannel::class : null,
        ];
        // Et seulement ceux que le compte garde pour l'activité de son compte (décision 2026-11-27-notifications-du-profil).
        $chosen = array_flip($this->notificationSettings()[NotificationSettings::ACCOUNT_ACTIVITY]);

        return array_values(array_filter(array_intersect_key($available, $chosen)));
    }

    /**
     * Ses choix de notifications, complétés par les défauts (décision 2026-11-27-notifications-du-profil).
     *
     * @return array{account_activity: list<string>, reminders: list<string>, offers: bool, news: bool}
     */
    public function notificationSettings(): array
    {
        return NotificationSettings::resolve($this->notification_settings);
    }

    /**
     * Le numéro du canal SMS : vérifié seulement.
     */
    public function routeNotificationForSms(Notification $notification): ?string
    {
        return $this->phone_verified_at !== null ? $this->phone : null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin() || $this->hasPermissionTo(PermissionEnum::ACCESS_ADMIN);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleEnum::SUPER_ADMIN);
    }
}
