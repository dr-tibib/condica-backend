export const savedPlacesMock = [
    {
        id: 1,
        google_place_id: "ChIJ12345",
        name: "MATIKON",
        address: "Sediul Industrial",
        photo_reference: "https://lh3.googleusercontent.com/photo1",
        latitude: 45.123,
        longitude: 25.456
    },
    {
        id: 2,
        google_place_id: "ChIJ67890",
        name: "MATION",
        address: "Depozit Logistic",
        photo_reference: "https://lh3.googleusercontent.com/photo2",
        latitude: 45.789,
        longitude: 25.012
    }
];

export const googlePredictionsMock = [
    {
        place_id: "ChIJabcd",
        description: "Dedeman, Strada Lunca Oltului, Sfântu Gheorghe",
        structured_formatting: {
            main_text: "Dedeman",
            secondary_text: "Strada Lunca Oltului, Sfântu Gheorghe"
        }
    },
    {
        place_id: "ChIJefgh",
        description: "Dedeman, Strada Hărmanului, Brașov",
        structured_formatting: {
            main_text: "Dedeman",
            secondary_text: "Strada Hărmanului, Brașov"
        }
    }
];

export const googlePlaceDetailsMock = {
    displayName: "Dedeman",
    formattedAddress: "Strada Lunca Oltului, Sfântu Gheorghe",
    location: {
        lat: () => 45.863,
        lng: () => 25.787
    },
    photos: [
        {
            getURI: () => "https://lh3.googleusercontent.com/photo_dedeman"
        }
    ]
};
