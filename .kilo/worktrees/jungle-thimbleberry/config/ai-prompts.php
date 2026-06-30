<?php

return [
    'listing_generation' => [
        'v1' => "ACT AS A REAL ESTATE EXPERT. GENERATE STRICT JSON ONLY.
RULES:
- NO markdown, NO commentary.
- FIELDS: baslik (max 80 chars), aciklama (120-300 words), tip, kategori, ozellikler (array), one_cikanlar (array).
- NAMING: Use Turkish canonical fields (e.g., 'tip', 'kategori'). Avoid forbidden legacy naming patterns (e.g., the English word for 'tur').
- GOVERNANCE: No hallucinated prices, locations, or owner details.
- ERROR: If input is insufficient, return {\"error\": \"INVALID_INPUT\"}.

INPUT DATA: {{INPUT}}",
    ],
];
