<style>
    body { 
        font-family: DejaVu Sans, sans-serif; 
        font-size: 10px; 
        color: #111; 
    }

    h1 { 
        font-size: 16px; 
        margin: 0 0 10px 0; 
    }

    .meta { 
        font-size: 10px; 
        margin-bottom: 10px; 
        color: #333; 
    }

    table { 
        width: 100%; 
        border-collapse: collapse; 
        table-layout: fixed; /* KLJUČNO za PDF */
    }

    th, td { 
        border: 1px solid #222; 
        padding: 4px 6px; 
        vertical-align: middle;

        /* 🔥 VAŽNO ZA P OZNAKE */
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }

    th { 
        background: #eee; 
        font-weight: bold; 
        text-align: left; 
    }

    .center { 
        text-align: center; 
    }

    /* Posebno agresivno lomljenje (za P oznake ako trebaš) */
    .break-all {
        word-break: break-all;
        overflow-wrap: anywhere;
        white-space: normal;
    }

    /* ✅ STANDARD ZNR LIDER */
    .rok-expired { 
        background: #ff0000; 
        color: #ffffff; 
        font-weight: bold; 
    }

    .rok-soon { 
        background: #ffff00; 
        color: #000000; 
        font-weight: bold; 
    }