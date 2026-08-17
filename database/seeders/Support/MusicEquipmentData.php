<?php

namespace Database\Seeders\Support;

/**
 * Catalog of realistic music studio / university music club equipment,
 * grouped by category. Used by ItemSeeder and ItemFactory so no
 * Faker Lorem Ipsum text ends up in the items table.
 */
class MusicEquipmentData
{
    /** @var array<string, array<int, array{0: string, 1: string, 2: string, 3: string}>> Category => [name, manufacturer, model, description] */
    private static array $catalog = [
        'Guitars' => [
            ['Yamaha F310 Acoustic Guitar', 'Yamaha', 'F310', 'Full-size dreadnought acoustic guitar, ideal for beginner practice and rehearsals.'],
            ['Yamaha FG800 Acoustic Guitar', 'Yamaha', 'FG800', 'Solid spruce top dreadnought acoustic guitar with rich, balanced tone.'],
            ['Fender Player Stratocaster', 'Fender', 'Player Stratocaster', 'Electric guitar with three single-coil pickups, used for band performances.'],
            ['Squier Classic Vibe Telecaster', 'Squier', 'Classic Vibe Telecaster', 'Vintage-style electric guitar with a bright, twangy tone.'],
            ['Ibanez RG421', 'Ibanez', 'RG421', 'Electric guitar with humbucker pickups, suited for rock and metal performances.'],
            ['Yamaha Pacifica 112V', 'Yamaha', 'Pacifica 112V', 'Versatile electric guitar with humbucker-single-single pickup configuration.'],
            ['Epiphone Les Paul Standard', 'Epiphone', 'Les Paul Standard', 'Electric guitar with humbucker pickups and a mahogany body for a warm tone.'],
            ['Ibanez GIO Bass Guitar', 'Ibanez', 'GIO GSR200', '4-string electric bass guitar used for band rehearsals and gigs.'],
            ['Cordoba C5 Classical Guitar', 'Cordoba', 'C5', 'Nylon-string classical guitar with solid cedar top.'],
        ],
        'Keyboards & Pianos' => [
            ['Yamaha PSR-E373 Keyboard', 'Yamaha', 'PSR-E373', '61-key portable keyboard with built-in voices and rhythms for practice sessions.'],
            ['Yamaha PSR-EW310 Keyboard', 'Yamaha', 'PSR-EW310', '76-key portable keyboard with touch response, used for ensemble practice.'],
            ['Roland FP-30X Digital Piano', 'Roland', 'FP-30X', '88-key weighted digital piano with built-in speakers for recitals.'],
            ['Casio CT-X700 Keyboard', 'Casio', 'CT-X700', '61-key keyboard with AiX sound source for performances and practice.'],
            ['Korg Kross 2 Synthesizer', 'Korg', 'Kross 2', '61-key synthesizer workstation used for stage performances.'],
            ['Korg microKEY MIDI Controller', 'Korg', 'microKEY-37', '37-key USB MIDI controller for recording and production sessions.'],
        ],
        'Microphones' => [
            ['Shure SM58 Vocal Mic', 'Shure', 'SM58', 'Dynamic cardioid vocal microphone, the industry standard for live vocals.'],
            ['Shure SM57 Instrument Mic', 'Shure', 'SM57', 'Dynamic cardioid microphone commonly used on instruments and amps.'],
            ['Rode NT1 Studio Mic', 'Rode', 'NT1', 'Large-diaphragm condenser microphone for studio vocal recording.'],
            ['Audio-Technica AT2020 Condenser Mic', 'Audio-Technica', 'AT2020', 'Cardioid condenser microphone for vocal and instrument recording.'],
            ['Sennheiser e835 Mic', 'Sennheiser', 'e835', 'Dynamic cardioid microphone for live vocal performances.'],
            ['AKG P120 Condenser Mic', 'AKG', 'P120', 'High-performance cardioid condenser microphone for project studios.'],
        ],
        'Speakers & Amplifiers' => [
            ['JBL EON615 PA Speaker', 'JBL', 'EON615', '15-inch powered PA speaker used for events and stage performances.'],
            ['Yamaha Stagepas 400BT PA System', 'Yamaha', 'Stagepas 400BT', 'Portable PA system with Bluetooth, used for small events and rehearsals.'],
            ['Behringer Xenyx Q1202USB Mixer', 'Behringer', 'Xenyx Q1202USB', '12-input analog mixer with USB audio interface for recording.'],
            ['Marshall MG30 Guitar Amp', 'Marshall', 'MG30GFX', '30-watt combo guitar amplifier with built-in effects.'],
            ['Fender Rumble 40 Bass Amp', 'Fender', 'Rumble 40', '40-watt bass combo amplifier for rehearsals and small gigs.'],
            ['Focusrite Scarlett 2i2 Audio Interface', 'Focusrite', 'Scarlett 2i2', '2-input USB audio interface for recording sessions.'],
            ['Behringer Ultratone Keyboard Amp', 'Behringer', 'Ultratone K900FX', 'Keyboard amplifier with built-in effects and mixer channels.'],
        ],
        'Drums & Percussion' => [
            ['Pearl Export Drum Kit', 'Pearl', 'Export EXX', '5-piece acoustic drum kit with cymbals, used for band rehearsals.'],
            ['Yamaha Stage Custom Drum Kit', 'Yamaha', 'Stage Custom Birch', '5-piece birch acoustic drum kit for performances and recording.'],
            ['Roland TD-1DMK Electronic Drum Kit', 'Roland', 'TD-1DMK', 'Electronic drum kit with mesh heads for quiet practice sessions.'],
            ['Meinl Cajon', 'Meinl', 'Headliner Cajon', 'Wooden box percussion instrument used for acoustic performances.'],
            ['LP Bongo Set', 'Latin Percussion', 'LP601NY', 'Pair of bongo drums used for percussion ensembles.'],
        ],
        'Cables & Accessories' => [
            ['XLR Cable 5m', 'Generic', 'XLR-5M', '5-metre XLR cable for connecting microphones and audio equipment.'],
            ['Guitar Instrument Cable', 'Generic', 'TS-6M', '6-metre instrument cable for guitars, basses and keyboards.'],
            ['Microphone Stand', 'Generic', 'MS-Boom', 'Adjustable boom microphone stand for live performances and recording.'],
            ['Keyboard Stand', 'Generic', 'KS-X', 'X-frame adjustable keyboard stand for stage and rehearsal use.'],
            ['Music Stand', 'Generic', 'MS-Folding', 'Foldable music stand for sheet music during rehearsals and recitals.'],
            ['Guitar Strap', 'Generic', 'GS-Padded', 'Padded adjustable strap for acoustic and electric guitars.'],
            ['Guitar Tuner', 'Korg', 'GA-50', 'Clip-on chromatic tuner for guitars and basses.'],
            ['Guitar Capo', 'Kyser', 'Quick-Change', 'Spring-loaded capo for acoustic and electric guitars.'],
        ],
        'Traditional Instruments' => [
            ['Caklempong Set', 'Warisan Craft', 'Caklempong-Standard', 'Set of small gongs arranged for traditional Minangkabau-Malay ensemble performances.'],
            ['Kompang Set', 'Warisan Craft', 'Kompang-12', 'Set of 12 hand-held frame drums used in traditional Malay performances.'],
            ['Rebana Set', 'Warisan Craft', 'Rebana-Ubi', 'Set of large traditional frame drums for cultural performances.'],
            ['Gambus', 'Warisan Craft', 'Gambus-Hijaz', 'Traditional short-necked lute used in Malay and Arabic-influenced music.'],
            ['Angklung Set', 'Saung Angklung', 'Angklung-Diatonic', 'Set of bamboo shake instruments tuned diatonically for ensemble play.'],
        ],
        'Performance Uniforms' => [
            ['Choir Uniform (Male)', 'UTeM Music Club', 'Choir-M', 'Standard male choir uniform set for choir performances.'],
            ['Choir Uniform (Female)', 'UTeM Music Club', 'Choir-F', 'Standard female choir uniform set for choir performances.'],
            ['Band Performance Vest', 'UTeM Music Club', 'Band-Vest', 'Vest worn by band members during official performances.'],
            ['Orchestra Vest', 'UTeM Music Club', 'Orchestra-Vest', 'Formal vest worn by orchestra members during recitals.'],
        ],
        'Traditional Costumes' => [
            ['Baju Melayu Performance Set', 'UTeM Music Club', 'Baju-Melayu', 'Traditional Baju Melayu set worn for cultural and traditional performances.'],
            ['Baju Kurung Performance Set', 'UTeM Music Club', 'Baju-Kurung', 'Traditional Baju Kurung set worn for cultural and traditional performances.'],
            ['Caklempong Performance Costume', 'UTeM Music Club', 'Caklempong-Costume', 'Matching costume set worn by the Caklempong ensemble during performances.'],
            ['Traditional Costume Set', 'UTeM Music Club', 'Traditional-Set', 'General traditional costume set used for cultural showcases.'],
        ],
    ];

    /** @var array<string, int> Pointer into the catalog for each category, for factory cycling */
    private static array $cursor = [];

    public static function itemsForCategory(string $categoryName): array
    {
        return self::$catalog[$categoryName] ?? [];
    }

    public static function categories(): array
    {
        return array_keys(self::$catalog);
    }

    /**
     * Pick the next catalog entry for a category (cycling through if exhausted),
     * used by the factory to generate "extra" realistic items.
     */
    public static function nextEntry(string $categoryName): array
    {
        $items = self::itemsForCategory($categoryName);

        if (empty($items)) {
            $categoryName = 'Cables & Accessories';
            $items = self::itemsForCategory($categoryName);
        }

        $cursor = self::$cursor[$categoryName] ?? 0;
        $entry  = $items[$cursor % count($items)];
        self::$cursor[$categoryName] = $cursor + 1;

        return $entry;
    }

    public static function condition(): string
    {
        return fake()->randomElement(['good', 'good', 'good', 'fair', 'fair', 'poor']);
    }
}
