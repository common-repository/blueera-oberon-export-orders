<?php
function be_export_objednavok_get_order_payment_method_price($order)
{
    // filtered in config.php
    return wc_round_tax_total(apply_filters('beeo_order_gateways_fees_value', 0, $order), 2);
}

function be_esc_xml($text){
    if(function_exists('esc_xml')){ return esc_xml($text); }

    $safe_text = wp_check_invalid_utf8( $text );
 
    $cdata_regex = '\<\!\[CDATA\[.*?\]\]\>';
    $regex       = <<<EOF
/
    (?=.*?{$cdata_regex})                 # lookahead that will match anything followed by a CDATA Section
    (?<non_cdata_followed_by_cdata>(.*?)) # the "anything" matched by the lookahead
    (?<cdata>({$cdata_regex}))            # the CDATA Section matched by the lookahead
 
|                                         # alternative
 
    (?<non_cdata>(.*))                    # non-CDATA Section
/sx
EOF;
 
    $safe_text = (string) preg_replace_callback(
        $regex,
        static function( $matches ) {
            if ( ! isset( $matches[0] ) ) {
                return '';
            }
 
            if ( isset( $matches['non_cdata'] ) ) {
                // escape HTML entities in the non-CDATA Section.
                return _wp_specialchars( $matches['non_cdata'], ENT_XML1 );
            }
 
            // Return the CDATA Section unchanged, escape HTML entities in the rest.
            return _wp_specialchars( $matches['non_cdata_followed_by_cdata'], ENT_XML1 ) . $matches['cdata'];
        },
        $safe_text
    );
 
    return apply_filters( 'esc_xml', $safe_text, $text );
}


function be_export_objednavok_oberon_xml($order_ids)
{
    if (count($order_ids) == 0) {
        return;
    }
    $DocumentTime = date('d.m.Y H:i:s'); //for example 12.05.2021 09:47:31
    $CompanyName = apply_filters('OberonCompanyName', '');
    $CompanyStreet = WC()->countries->get_base_address();
    $CompanyPostalCode = WC()->countries->get_base_postcode();
    $CompanyCity = WC()->countries->get_base_city();
    $CompanyICO = apply_filters('OberonCompanyICO', '');
    $CompanyDIC = apply_filters('OberonCompanyDIC', '');
    $CompanyICDPH = apply_filters('OberonCompanICDPH', '');
    $CompanyWEBurl = apply_filters('OberonCompanyWEBurl', site_url());
    $User = apply_filters('OberonUser', 'Admin');
    $OBERONversion = apply_filters('OberonVersion', 'Marec/2021  SP1');
    $OBERONversionDB = apply_filters('OberonVersionDB', '395');
    $CisloSkladu = '1';
    $PercentoZnizenejSadzbyDPH = apply_filters('OberonPercentoZnizenejDPH', 10);
    $PercentoDPH = apply_filters('OberonPercentoDPH', 20);

    $returnstring = '';
    $return = '';
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        $IDNum = '';
        $DocumentNumber = $order->get_order_number(); //hlavny identifikator objednavky v OBERONE
        $Number = $order->get_order_number(); //OP-15001
        $OrderDate = date('j.n.Y', strtotime($order->get_date_created())); //31.12.2015
        $CustomerName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $CustomerStreet = $order->get_billing_address_1();
        $CustomerPostalCode = $order->get_billing_postcode();
        $CustomerCity = $order->get_billing_city();
        $CustomerICO = apply_filters('OrderOberonCustomerICO', '', $order);
        $CustomerDIC = apply_filters('OrderOberonCustomerDIC', '', $order);
        $CustomerICDPH = apply_filters('OrderOberonCustomerICDPH', '', $order);
        $OrderShippment = $order->get_shipping_method();
        $CustomerEmail =  $order->get_billing_email();
        $CustomerPhone =  $order->get_billing_phone();
        $CurrencyCode = $order->get_currency();
        $CenaCelkomSdph = $order->get_total();
        $CenaBezDPHznizenaSadzbaDPH = '0';
        $CenaCelkomBezDPH = $order->get_total() - $order->get_total_tax();
        $SumaDPHvZnizenejSadzbe = '0';
        $SumaDPH = $order->get_total_tax();
        $OrderStatus = __('Nová objednávka');
        $OrderDeliveryDate = date('d.m.Y H:i:s', strtotime(apply_filters('OrderOberonOrderDeliveryDate', $DocumentTime, $order)));

        $return .= '<Record IDNum="' . esc_attr($IDNum) . '" Number="' . esc_attr($Number) . '">
    <!--Číslo objednávky - čísluje sa automaticky. Ak sa objednávky importujú z internetového obchodu, číslo objednávky je potrebné uviesť do poľa Document_Number_External.-->
    <Document_Number>' . be_esc_xml($DocumentNumber) . '</Document_Number>
    <!--Typ dokladu, Napr. objednávka, ponuka, prípadne ich ekvivalenty v cudzej mene.-->
    <Document_Type>Objednávka</Document_Type>
    <!--Dátum a čas evidencie /vytvorenia, zaevidovanie/ objednávky. Pri prepojení s internetovým obchodom je to okamih vytvorenia objednávky na e-shope.-->
    <DateTime_Document>' . be_esc_xml($OrderDate) . '</DateTime_Document>
    <!--Dátum predpokladaného dodania tovaru/služby.-->
    <Date_Delivery>' . be_esc_xml($OrderDeliveryDate) . '</Date_Delivery>
    <!--Ak je zadaný dátum, položky v objednávke sa považujú do daného dátumu za rezervované.-->
    <Date_Reservation></Date_Reservation>
    <!--Externé číslo objednávky - číslo objednávky od zákazníka (odberateľa), prípadne číslo objednávky vytvorenej v internetovom obchode.-->
    <Document_Number_External>' . be_esc_xml($Number) . '</Document_Number_External>
    <!--Údaje o účtovnej jednotke, ktorá doklad vytvorila.-->
    <Company>
        <!--Obchodný názov účtovnej jednotky (firmy).-->
        <Name>' . be_esc_xml($CompanyName) . '</Name>
        <!--Adresa sídla firmy.-->
        <Address_Residence>
            <Street>' . be_esc_xml($CompanyStreet) . '</Street>
            <Region></Region>
            <PostalCode>' . be_esc_xml($CompanyPostalCode) . '</PostalCode>
            <City>' . be_esc_xml($CompanyCity) . '</City>
            <Country></Country>
            <Notice></Notice>
        </Address_Residence>
        <!--IČO firmy.-->
        <IdentificationNumber>' . be_esc_xml($CompanyICO) . '</IdentificationNumber>
        <!--DIČ firmy.-->
        <IdentificationNumber_Tax>' . be_esc_xml($CompanyDIC) . '</IdentificationNumber_Tax>
        <!--IČ DPH firmy.-->
        <IdentificationNumber_VAT>' . be_esc_xml($CompanyICDPH) . '</IdentificationNumber_VAT>
        <!--Riadok 1 textu obchodného registra.-->
        <BusinessRegister_1></BusinessRegister_1>
        <!--Riadok 2 textu obchodného registra.-->
        <BusinessRegister_2></BusinessRegister_2>
        <!--Colný úrad - číslo povolenia na predaj alkoholu (uvádzajú veľkoobchody s predajom alkoholu).-->
        <CustomsOffice_Number_SBL></CustomsOffice_Number_SBL>
        <!--Emailová adresa.-->
        <EMail></EMail>
        <!--Telefónne číslo.-->
        <PhoneNumber></PhoneNumber>
        <!--Adresa internetovej stránky firmy.-->
        <Web>' . be_esc_xml($CompanyWEBurl) . '</Web>
        <Branch>
            <Name></Name>
            <Address_Residence>
                <Street></Street>
                <Region></Region>
                <PostalCode></PostalCode>
                <City></City>
                <Country></Country>
                <Notice></Notice>
            </Address_Residence>
        </Branch>
    </Company>
    <!--Text pred položkami. Môže byť čistý text, alebo HTML.-->
    <Document_Text_BeforeItems>
        <![CDATA[]]>
    </Document_Text_BeforeItems>
    <!--Text za položkami. Môže byť čistý text, alebo HTML.-->
    <Document_Text_AfterItems>
        <![CDATA[]]>
    </Document_Text_AfterItems>
    <!--Poznámka k objednávke.-->
    <Notice></Notice>
    <Person_Registered>' . be_esc_xml($User) . '</Person_Registered>
    <!--Osoba ktorá sa tovar/služby objednala.-->
    <Person_Ordered></Person_Ordered>
    <!--Členenie dokladu v rámci systému OBERON (stromová štruktúra).-->
    <Document_Classification></Document_Classification>
    <!--Stav objednávky (textová hodnota). Ak nebude hodnota zadaná, pri importe novej objednávke systém OBERON vygeneruje stav automaticky.-->
    <OrderState>' . be_esc_xml($OrderStatus) . '</OrderState>
    <!--Stav objednávky - číselná hodnota z číselníka systému OBERON. Ak nebude hodnota zadaná, pri importe novej objednávke systém OBERON vygeneruje stav automaticky.-->
    <OrderStateNumber></OrderStateNumber>
    <!--Stav objednávky - číselná hodnota z číselníka systému OBERON (hodnota pre eShop). Ak nebude hodnota zadaná, pri importe novej objednávke systém OBERON vygeneruje stav automaticky.-->
    <OrderStateEShopNumber></OrderStateEShopNumber>
    <!--Príznak,či je objednávka bez cien.-->
    <IsItemsWithoutPrices>0</IsItemsWithoutPrices>
    <!--Skratka používateľa, ktorý doklad vytvoril.-->
    <User_Add>' . be_esc_xml(substr($User, 0, 1)) . '</User_Add>
    <!--Skratka používateľa, ktorý doklad naposledy aktualizoval.-->
    <User_LastUpdate></User_LastUpdate>
    <!--*** Údaje o obchodnom partnerovi (odberateľovi) ***-->
    <BusinessPartner IDNum="' . esc_attr($IDNum) . '">
        <Name>' . be_esc_xml($CustomerName) . '</Name>
        <Address_Residence>
            <Street>' . be_esc_xml($CustomerStreet) . '</Street>
            <Region></Region>
            <PostalCode>' . be_esc_xml($CustomerPostalCode) . '</PostalCode>
            <City>' . be_esc_xml($CustomerCity) . '</City>
            <Country></Country>
            <Notice></Notice>
            <CountryCode></CountryCode>
        </Address_Residence>
        <IdentificationNumber>' . be_esc_xml($CustomerICO) . '</IdentificationNumber>
        <IdentificationNumber_Tax>' . be_esc_xml($CustomerDIC) . '</IdentificationNumber_Tax>
        <IdentificationNumber_VAT>' . be_esc_xml($CustomerICDPH) . '</IdentificationNumber_VAT>
        <ExternalSystem_ID></ExternalSystem_ID>
        <CustomsOffice_Number_SBL></CustomsOffice_Number_SBL>
        <Email>' . be_esc_xml($CustomerEmail) . '</Email>
        <PhoneNumber>' . be_esc_xml($CustomerPhone) . '</PhoneNumber>
        <Notice></Notice>
        <Branch>
            <Caption></Caption>
            <Name></Name>
            <Address_Residence>
                <Street></Street>
                <Region></Region>
                <PostalCode></PostalCode>
                <City></City>
                <Country></Country>
                <Notice></Notice>
            </Address_Residence>
        </Branch>
    </BusinessPartner>
    <!--*** Celková rekapitulácia súm dokladu ***-->
    <Document_PriceValues>
        <!--Kód meny dokladu. Ide o základnú menu, v ktorej je doklad vytvorený.-->
        <CurrencyCode>' . be_esc_xml($CurrencyCode) . '</CurrencyCode>
        <!--Nižšia sazba DPH.-->
        <VAT_Rate_Lower>' . be_esc_xml($PercentoZnizenejSadzbyDPH) . '</VAT_Rate_Lower>
        <!--Vyššia sazba DPH.-->
        <VAT_Rate_Upper>' . be_esc_xml($PercentoDPH) . '</VAT_Rate_Upper>
        <!--Celková suma dokladu.-->
        <Price_Total>' . be_esc_xml($CenaCelkomSdph) . '</Price_Total>
        <!--Suma základu DPH v nižšej sadzbe.-->
        <Price_VAT_Base_Lower>' . be_esc_xml($CenaBezDPHznizenaSadzbaDPH) . '</Price_VAT_Base_Lower>
        <!--Suma základu DPH vo vyššej sadzbe.-->
        <Price_VAT_Base_Upper>' . be_esc_xml($CenaCelkomBezDPH) . '</Price_VAT_Base_Upper>
        <!--Suma DPH v nižšej sadzbe DPH.-->
        <Price_VAT_Lower>' . be_esc_xml($SumaDPHvZnizenejSadzbe) . '</Price_VAT_Lower>
        <!--Suma DPH vo vyššej sadzbe DPH.-->
        <Price_VAT_Upper>' . be_esc_xml($SumaDPH) . '</Price_VAT_Upper>
        <!--Suma s nulovu sadzbou DPH (oslobodené od DPH)-->
        <Price_VAT_Base_Zero>0</Price_VAT_Base_Zero>
        <!--Suma zaokrúhlenia rekapitulácie súm dokladu.-->
        <Price_Rounding>0</Price_Rounding>
        <!--Celková hodnota poskytnutých zliav v percentách (má len informačný charakter).-->
        <Discount_Percento>0</Discount_Percento>
        <!--Celková suma poskytnutých zliav (má len informačný charakter).-->
        <Discount_Value>0</Discount_Value>
        <!--Ak je doklad v cudzej mene, tu sa uvádza kód cudzej meny (foreign currency)-->
        <FC_CurrencyCode></FC_CurrencyCode>
        <!--Ak je doklad v cudzej mene, tu sa uvádza kurz cudzej meny (foreign currency). Niektoré krajiny môžu mať tzv. otočený kurz (je možné nastaviť v číselníku cudzích mien).-->
        <FC_ExchangeRate>0</FC_ExchangeRate>
        <!--Celková suma dokladu v cudzej mene.-->
        <FC_Price_Total>0</FC_Price_Total>
        <!--Základ DPH v nižšej sadzbe v cudzej mene.-->
        <FC_Price_VAT_Base_Lower>0</FC_Price_VAT_Base_Lower>
        <!--Základ DPH vo vyššej sadzbe v cudzej mene.-->
        <FC_Price_VAT_Base_Upper>0</FC_Price_VAT_Base_Upper>
        <!--Výška DPH v nižšej sadzbe prepočítaná do cudzej meny.-->
        <FC_Price_VAT_Lower>0</FC_Price_VAT_Lower>
        <!--Výška DPH vo vyššej sadzbe prepočítaná do cudzej meny.-->
        <FC_Price_VAT_Upper>0</FC_Price_VAT_Upper>
        <!--Suma s nulovu sadzbou DPH v cudzej mene (oslobodené od DPH).-->
        <FC_Price_VAT_Base_Zero>0</FC_Price_VAT_Base_Zero>
        <!--Suma zaokrúhlenia rekapitulácie súm dokladu v cudzej mene.-->
        <FC_Price_Rounding>0</FC_Price_Rounding>
    </Document_PriceValues>
    <!--*** Položky dokladu ***-->
    ';
        $return .= '<Items>';
        $items = $order->get_items();
        if ($items) {
            foreach ($items as $item) {
                // DPH
                $tax = new WC_Tax();
                $taxes = $tax->get_rates($item->get_tax_class());
                $rates = array_shift($taxes);
                $item_rate = round(array_shift($rates));
                // DPH

                $merna_jednotka = apply_filters('aoeo_order_item_merna_jednotka', 'ks', $item);
                $itemDPHsadzba = $item_rate;
                $itemBarcode = apply_filters('aoeo_order_item_barcode', '', $item);
                $priceWithoutTAX = $item->get_total();
                $koefDPH = 1 + ($itemDPHsadzba / 100);
                $priceWithTAX = round($item->get_total() * $koefDPH, 2);
                $price_WithVAT_WithoutDiscount = round($item->get_subtotal() * $koefDPH, 2);
                $itemproduct = wc_get_product($item->get_product_id());
                $itemDescription = '';
                $prodsku = __('Neskladová položka');

                if ($itemproduct) {
                    $prodsku = $itemproduct->get_sku();
                    $itemDescription = $itemproduct->get_description();
                }

                $itemDiscount = 0;
                //$itemProduct = wc_get_product($item->get_product_id());
                $return .= '<Item>
            <!--Číslo položky dokladu. Pri textových (neskladových) položkách nie je potrebné zadávať (dátový formát STRING)-->
            <Number>' . be_esc_xml($prodsku) . '</Number>
            <!--Názov položky dokladu (povinný).-->
            <Name>' . be_esc_xml($item->get_name()) . '</Name>
            <!--Jednoznančný identifikátor skladu v systéme OBERON, do ktorého patrí daná skladová karta. Pri exportoch/importoch sa nemusí uvádzať, synchronizácia skladovej položky sa vykoná podľa čísla, čiarového kódu a podobne (viz. nastavenia XML komunikácie.-->
            <IDNum_Stock>' . be_esc_xml($CisloSkladu) . '</IDNum_Stock>
            <!--Jednoznančný identifikátor skladovej karty v systéme OBERON. Pri exportoch/importoch sa nemusí uvádzať, synchronizácia skladovej položky sa vykoná podľa čísla, čiarového kódu a podobne.-->
            <IDNum_Stock_Card>' . be_esc_xml($item->get_product_id()) . '</IDNum_Stock_Card>
            <!--Jednoznančný identifikátor varianty skladovej karty v systéme OBERON. Pri exportoch/importoch sa nemusí uvádzať.-->
            <IDNum_Stock_Card_Variant>' . be_esc_xml($item->get_variation_id()) . '</IDNum_Stock_Card_Variant>
            <!--Merná jednotka (povinný údaj).-->
            <Unit>' . be_esc_xml($merna_jednotka) . '</Unit>
            <!--Alternatívna merná jednotka (nepovinný).-->
            <Unit_Other></Unit_Other>
            <!--Koeficient prepočtu základnej mernej jednotky vôči alternatívnej mernej jednotky (nepovinný), napr. Cement evidovaný v [t], je možné prijať ako 25 kg vrecia v MJ [ks], pričom koeficient by bol 0.025.-->
            <Unit_Other_Coeficient>0</Unit_Other_Coeficient>
            <!--Sadzba DPH (povinný údaj).-->
            <VAT_Rate>' . be_esc_xml($itemDPHsadzba) . '</VAT_Rate>
            <!--Celková cena za položku bez DPH (nepovinný údaj).-->
            <Price_WithoutVAT>' . be_esc_xml($priceWithoutTAX) . '</Price_WithoutVAT>
            <!--Jednotková cena bez DPH (nepovinný).-->
            <Price_WithoutVAT_Unit>' . be_esc_xml($priceWithoutTAX) . '</Price_WithoutVAT_Unit>
            <!--Jednotková cena bez DPH za alternatívnu MJ (nepovinný).-->
            <Price_WithoutVAT_Unit_Other>0</Price_WithoutVAT_Unit_Other>
            <!--Celková cena s DPH. Spravidla sa od tejto hodnoty vykonáva import cien, pričom sa následne celá položka prepočíta (vypočítajú sa ceny bez DPH).-->
            <Price_WithVAT>' . be_esc_xml($priceWithTAX) . '</Price_WithVAT>
            <!--Jednotková cena s DPH. Ak nie je zadanu celková cena za položku s DPH, pre import bude použitá táto jednotková cena.-->
            <Price_WithVAT_Unit>' . be_esc_xml($priceWithTAX) . '</Price_WithVAT_Unit>
            <!--Jednotková cena s DPH za alternatívnu MJ.-->
            <Price_WithVAT_Unit_Other>0</Price_WithVAT_Unit_Other>
            <!--Celková cena s DPH pred uplatnením zľavy. Je potrebný, ak je na položke uplatnená zľava.-->
            <Price_WithVAT_WithoutDiscount>' . be_esc_xml($price_WithVAT_WithoutDiscount) . '</Price_WithVAT_WithoutDiscount>
            <!--Množstvo v základnej MJ (povinný údaj).-->
            <Amount_Unit>' . be_esc_xml($item->get_quantity()) . '</Amount_Unit>
            <!--Množstvo v alternatívnej MJ (nepovinný). Zadáva sa len vtedy, ak je zadaná alternatívna MJ.-->
            <Amount_UnitOther>0</Amount_UnitOther>
            <!--Čiarový kód položky.-->
            <BarCode>' . be_esc_xml($itemBarcode) . '</BarCode>
            <!--Kód položky pri predaji na pokladnici.-->
            <CashRegisterCode></CashRegisterCode>
            <!--Číslo colného sadzobníka. Od tohoto čísla sa odvodzuje tzv. prenesenie daňovej povinnosti.-->
            <CustomsTariffCode></CustomsTariffCode>
            <!--Percento zľavy (povinný údaj). Ak je tu uvedené hodnota, je potrebné vypniť aj cenu pred zľavou.-->
            <Discount>' . be_esc_xml($itemDiscount) . '</Discount>
            <!--Názov varianty položky (nepovinný údaj).-->
            <VariantName></VariantName>
            <!--Poznámka k položke (nepovinný).-->
            <Notice>' . be_esc_xml($itemDescription) . '</Notice>
            <!-- - - -  CUDZIE MENY - - - -->
            <!--Kód cudzej meny (nepovinný).-->
            <FC_CurrencyCode></FC_CurrencyCode>
            <!--Cudzia mena - Celková cena bez DPH (nepovinný).-->
            <FC_Price_WithoutVAT>0</FC_Price_WithoutVAT>
            <!--Cudzia mena - Jednotková cena bez DPH (nepovinný).-->
            <FC_Price_WithoutVAT_Unit>0</FC_Price_WithoutVAT_Unit>
            <!--Cudzia mena - Jednotková cena bez DPH za alternatívnu MJ.-->
            <FC_Price_WithoutVAT_Unit_Other>0</FC_Price_WithoutVAT_Unit_Other>
            <!--Cudzia mena - Celková cena s DPH.-->
            <FC_Price_WithVAT>0</FC_Price_WithVAT>
            <!--Cudzia mena - Jednotková cena s DPH (nepovinný).-->
            <FC_Price_WithVAT_Unit>0</FC_Price_WithVAT_Unit>
            <!--Cudzia mena - Jednotková cena s DPH za alternatívnu MJ.-->
            <FC_Price_WithVAT_Unit_Other>0</FC_Price_WithVAT_Unit_Other>
            <!--Cudzia mena - Celková cena s DPH pred uplatnením zľavy.-->
            <FC_Price_WithVAT_WithoutDiscount>0</FC_Price_WithVAT_WithoutDiscount>
            <Amount_Delivered>0</Amount_Delivered>
            <Amount_Reserved>0</Amount_Reserved>
            <Price_Supply>0</Price_Supply>
        </Item>';
            }

            //SHIPPING
            $order_shipping_total_exc_tax = $order->get_shipping_total();
            $order_shipping_tax = $order->get_shipping_tax();
            $order_shipping_total_with_tax = wc_round_tax_total($order_shipping_total_exc_tax + $order_shipping_tax, 2);
            if (floatval($order_shipping_total_exc_tax) > 0) {
                $merna_jednotka = 'ks';
                $itemDPHsadzba = apply_filters('OberonPercentoDPH', 20);
                $itemBarcode = '';
                $priceWithoutTAX = $order_shipping_total_exc_tax;
                $priceWithTAX = $order_shipping_total_with_tax;
                $price_WithVAT_WithoutDiscount = $order_shipping_total_with_tax;
                $return .= '<Item>
                            <!--Číslo položky dokladu. Pri textových (neskladových) položkách nie je potrebné zadávať (dátový formát STRING)-->
                            <Number>Doprava</Number>
                            <!--Názov položky dokladu (povinný).-->
                            <Name>' . be_esc_xml($order->get_shipping_method()) . '</Name>
                            <!--Jednoznančný identifikátor skladu v systéme OBERON, do ktorého patrí daná skladová karta. Pri exportoch/importoch sa nemusí uvádzať, synchronizácia skladovej položky sa vykoná podľa čísla, čiarového kódu a podobne (viz. nastavenia XML komunikácie.-->
                            <IDNum_Stock>' . be_esc_xml($CisloSkladu) . '</IDNum_Stock>
                            <!--Jednoznančný identifikátor skladovej karty v systéme OBERON. Pri exportoch/importoch sa nemusí uvádzať, synchronizácia skladovej položky sa vykoná podľa čísla, čiarového kódu a podobne.-->
                            <IDNum_Stock_Card></IDNum_Stock_Card>
                            <!--Jednoznančný identifikátor varianty skladovej karty v systéme OBERON. Pri exportoch/importoch sa nemusí uvádzať.-->
                            <IDNum_Stock_Card_Variant></IDNum_Stock_Card_Variant>
                            <!--Merná jednotka (povinný údaj).-->
                            <Unit>' . be_esc_xml($merna_jednotka) . '</Unit>
                            <!--Alternatívna merná jednotka (nepovinný).-->
                            <Unit_Other></Unit_Other>
                            <!--Koeficient prepočtu základnej mernej jednotky vôči alternatívnej mernej jednotky (nepovinný), napr. Cement evidovaný v [t], je možné prijať ako 25 kg vrecia v MJ [ks], pričom koeficient by bol 0.025.-->
                            <Unit_Other_Coeficient>0</Unit_Other_Coeficient>
                            <!--Sadzba DPH (povinný údaj).-->
                            <VAT_Rate>' . be_esc_xml($itemDPHsadzba) . '</VAT_Rate>
                            <!--Celková cena za položku bez DPH (nepovinný údaj).-->
                            <Price_WithoutVAT>' . be_esc_xml($priceWithoutTAX) . '</Price_WithoutVAT>
                            <!--Jednotková cena bez DPH (nepovinný).-->
                            <Price_WithoutVAT_Unit>' . be_esc_xml($priceWithoutTAX) . '</Price_WithoutVAT_Unit>
                            <!--Jednotková cena bez DPH za alternatívnu MJ (nepovinný).-->
                            <Price_WithoutVAT_Unit_Other>0</Price_WithoutVAT_Unit_Other>
                            <!--Celková cena s DPH. Spravidla sa od tejto hodnoty vykonáva import cien, pričom sa následne celá položka prepočíta (vypočítajú sa ceny bez DPH).-->
                            <Price_WithVAT>' . be_esc_xml($priceWithTAX) . '</Price_WithVAT>
                            <!--Jednotková cena s DPH. Ak nie je zadanu celková cena za položku s DPH, pre import bude použitá táto jednotková cena.-->
                            <Price_WithVAT_Unit>' . be_esc_xml($priceWithTAX) . '</Price_WithVAT_Unit>
                            <!--Jednotková cena s DPH za alternatívnu MJ.-->
                            <Price_WithVAT_Unit_Other>0</Price_WithVAT_Unit_Other>
                            <!--Celková cena s DPH pred uplatnením zľavy. Je potrebný, ak je na položke uplatnená zľava.-->
                            <Price_WithVAT_WithoutDiscount>' . be_esc_xml($price_WithVAT_WithoutDiscount) . '</Price_WithVAT_WithoutDiscount>
                            <!--Množstvo v základnej MJ (povinný údaj).-->
                            <Amount_Unit>1</Amount_Unit>
                            <!--Množstvo v alternatívnej MJ (nepovinný). Zadáva sa len vtedy, ak je zadaná alternatívna MJ.-->
                            <Amount_UnitOther>0</Amount_UnitOther>
                            <!--Čiarový kód položky.-->
                            <BarCode>' . be_esc_xml($itemBarcode) . '</BarCode>
                            <!--Kód položky pri predaji na pokladnici.-->
                            <CashRegisterCode></CashRegisterCode>
                            <!--Číslo colného sadzobníka. Od tohoto čísla sa odvodzuje tzv. prenesenie daňovej povinnosti.-->
                            <CustomsTariffCode></CustomsTariffCode>
                            <!--Percento zľavy (povinný údaj). Ak je tu uvedené hodnota, je potrebné vypniť aj cenu pred zľavou.-->
                            <Discount>0</Discount>
                            <!--Názov varianty položky (nepovinný údaj).-->
                            <VariantName></VariantName>
                            <!--Poznámka k položke (nepovinný).-->
                            <Notice></Notice>
                            <!-- - - -  CUDZIE MENY - - - -->
                            <!--Kód cudzej meny (nepovinný).-->
                            <FC_CurrencyCode></FC_CurrencyCode>
                            <!--Cudzia mena - Celková cena bez DPH (nepovinný).-->
                            <FC_Price_WithoutVAT>0</FC_Price_WithoutVAT>
                            <!--Cudzia mena - Jednotková cena bez DPH (nepovinný).-->
                            <FC_Price_WithoutVAT_Unit>0</FC_Price_WithoutVAT_Unit>
                            <!--Cudzia mena - Jednotková cena bez DPH za alternatívnu MJ.-->
                            <FC_Price_WithoutVAT_Unit_Other>0</FC_Price_WithoutVAT_Unit_Other>
                            <!--Cudzia mena - Celková cena s DPH.-->
                            <FC_Price_WithVAT>0</FC_Price_WithVAT>
                            <!--Cudzia mena - Jednotková cena s DPH (nepovinný).-->
                            <FC_Price_WithVAT_Unit>0</FC_Price_WithVAT_Unit>
                            <!--Cudzia mena - Jednotková cena s DPH za alternatívnu MJ.-->
                            <FC_Price_WithVAT_Unit_Other>0</FC_Price_WithVAT_Unit_Other>
                            <!--Cudzia mena - Celková cena s DPH pred uplatnením zľavy.-->
                            <FC_Price_WithVAT_WithoutDiscount>0</FC_Price_WithVAT_WithoutDiscount>
                            <Amount_Delivered>0</Amount_Delivered>
                            <Amount_Reserved>0</Amount_Reserved>
                            <Price_Supply>0</Price_Supply>
                        </Item>';
            }
            //SHIPPING
            //PAYMENT
            $order_payment_total_exc_tax = be_export_objednavok_get_order_payment_method_price($order);
            $koefDPH = 1 + ($itemDPHsadzba / 100);
            $order_payment_total_with_tax = be_export_objednavok_get_order_payment_method_price($order) * $koefDPH;
            $order_payment_tax = wc_round_tax_total($order_payment_total_with_tax - $order_payment_total_exc_tax, 2);
            if (floatval($order_payment_total_exc_tax) > 0) {
                $merna_jednotka = 'ks';
                $itemDPHsadzba = apply_filters('OberonPercentoDPH', 20);
                $itemBarcode = '';
                $priceWithoutTAX = $order_payment_total_exc_tax;
                $priceWithTAX = $order_payment_total_with_tax;
                $price_WithVAT_WithoutDiscount = $order_payment_total_with_tax;
                $return .= '<Item>
                          <!--Číslo položky dokladu. Pri textových (neskladových) položkách nie je potrebné zadávať (dátový formát STRING)-->
                          <Number>Platba</Number>
                          <!--Názov položky dokladu (povinný).-->
                          <Name>' . be_esc_xml($order->get_payment_method_title()) . '</Name>
                          <!--Jednoznančný identifikátor skladu v systéme OBERON, do ktorého patrí daná skladová karta. Pri exportoch/importoch sa nemusí uvádzať, synchronizácia skladovej položky sa vykoná podľa čísla, čiarového kódu a podobne (viz. nastavenia XML komunikácie.-->
                          <IDNum_Stock>' . be_esc_xml($CisloSkladu) . '</IDNum_Stock>
                          <!--Jednoznančný identifikátor skladovej karty v systéme OBERON. Pri exportoch/importoch sa nemusí uvádzať, synchronizácia skladovej položky sa vykoná podľa čísla, čiarového kódu a podobne.-->
                          <IDNum_Stock_Card></IDNum_Stock_Card>
                          <!--Jednoznančný identifikátor varianty skladovej karty v systéme OBERON. Pri exportoch/importoch sa nemusí uvádzať.-->
                          <IDNum_Stock_Card_Variant></IDNum_Stock_Card_Variant>
                          <!--Merná jednotka (povinný údaj).-->
                          <Unit>' . be_esc_xml($merna_jednotka) . '</Unit>
                          <!--Alternatívna merná jednotka (nepovinný).-->
                          <Unit_Other></Unit_Other>
                          <!--Koeficient prepočtu základnej mernej jednotky vôči alternatívnej mernej jednotky (nepovinný), napr. Cement evidovaný v [t], je možné prijať ako 25 kg vrecia v MJ [ks], pričom koeficient by bol 0.025.-->
                          <Unit_Other_Coeficient>0</Unit_Other_Coeficient>
                          <!--Sadzba DPH (povinný údaj).-->
                          <VAT_Rate>' . be_esc_xml($itemDPHsadzba) . '</VAT_Rate>
                          <!--Celková cena za položku bez DPH (nepovinný údaj).-->
                          <Price_WithoutVAT>' . be_esc_xml($priceWithoutTAX) . '</Price_WithoutVAT>
                          <!--Jednotková cena bez DPH (nepovinný).-->
                          <Price_WithoutVAT_Unit>' . be_esc_xml($priceWithoutTAX) . '</Price_WithoutVAT_Unit>
                          <!--Jednotková cena bez DPH za alternatívnu MJ (nepovinný).-->
                          <Price_WithoutVAT_Unit_Other>0</Price_WithoutVAT_Unit_Other>
                          <!--Celková cena s DPH. Spravidla sa od tejto hodnoty vykonáva import cien, pričom sa následne celá položka prepočíta (vypočítajú sa ceny bez DPH).-->
                          <Price_WithVAT>' . be_esc_xml($priceWithTAX) . '</Price_WithVAT>
                          <!--Jednotková cena s DPH. Ak nie je zadanu celková cena za položku s DPH, pre import bude použitá táto jednotková cena.-->
                          <Price_WithVAT_Unit>' . be_esc_xml($priceWithTAX) . '</Price_WithVAT_Unit>
                          <!--Jednotková cena s DPH za alternatívnu MJ.-->
                          <Price_WithVAT_Unit_Other>0</Price_WithVAT_Unit_Other>
                          <!--Celková cena s DPH pred uplatnením zľavy. Je potrebný, ak je na položke uplatnená zľava.-->
                          <Price_WithVAT_WithoutDiscount>' . be_esc_xml($price_WithVAT_WithoutDiscount) . '</Price_WithVAT_WithoutDiscount>
                          <!--Množstvo v základnej MJ (povinný údaj).-->
                          <Amount_Unit>1</Amount_Unit>
                          <!--Množstvo v alternatívnej MJ (nepovinný). Zadáva sa len vtedy, ak je zadaná alternatívna MJ.-->
                          <Amount_UnitOther>0</Amount_UnitOther>
                          <!--Čiarový kód položky.-->
                          <BarCode>' . be_esc_xml($itemBarcode) . '</BarCode>
                          <!--Kód položky pri predaji na pokladnici.-->
                          <CashRegisterCode></CashRegisterCode>
                          <!--Číslo colného sadzobníka. Od tohoto čísla sa odvodzuje tzv. prenesenie daňovej povinnosti.-->
                          <CustomsTariffCode></CustomsTariffCode>
                          <!--Percento zľavy (povinný údaj). Ak je tu uvedené hodnota, je potrebné vypniť aj cenu pred zľavou.-->
                          <Discount>0</Discount>
                          <!--Názov varianty položky (nepovinný údaj).-->
                          <VariantName></VariantName>
                          <!--Poznámka k položke (nepovinný).-->
                          <Notice></Notice>
                          <!-- - - -  CUDZIE MENY - - - -->
                          <!--Kód cudzej meny (nepovinný).-->
                          <FC_CurrencyCode></FC_CurrencyCode>
                          <!--Cudzia mena - Celková cena bez DPH (nepovinný).-->
                          <FC_Price_WithoutVAT>0</FC_Price_WithoutVAT>
                          <!--Cudzia mena - Jednotková cena bez DPH (nepovinný).-->
                          <FC_Price_WithoutVAT_Unit>0</FC_Price_WithoutVAT_Unit>
                          <!--Cudzia mena - Jednotková cena bez DPH za alternatívnu MJ.-->
                          <FC_Price_WithoutVAT_Unit_Other>0</FC_Price_WithoutVAT_Unit_Other>
                          <!--Cudzia mena - Celková cena s DPH.-->
                          <FC_Price_WithVAT>0</FC_Price_WithVAT>
                          <!--Cudzia mena - Jednotková cena s DPH (nepovinný).-->
                          <FC_Price_WithVAT_Unit>0</FC_Price_WithVAT_Unit>
                          <!--Cudzia mena - Jednotková cena s DPH za alternatívnu MJ.-->
                          <FC_Price_WithVAT_Unit_Other>0</FC_Price_WithVAT_Unit_Other>
                          <!--Cudzia mena - Celková cena s DPH pred uplatnením zľavy.-->
                          <FC_Price_WithVAT_WithoutDiscount>0</FC_Price_WithVAT_WithoutDiscount>
                          <Amount_Delivered>0</Amount_Delivered>
                          <Amount_Reserved>0</Amount_Reserved>
                          <Price_Supply>0</Price_Supply>
                      </Item>';
            }
            //PAYMENT
        }
        $return .= '</Items>';
        $TransportTrackingNumber = apply_filters('aoeo_order_item_TransportTrackingNumber', 0, $order);
        $TransportTransportationEvidenceNumber = apply_filters('aoeo_order_item_TransportTransportationEvidenceNumber', 0, $order);
        $TransportTransportationTechnique = $OrderShippment;
        $TransportTransportationType = apply_filters('aoeo_order_item_TransportTransportationType', '', $order);
        $return .= '<!--*** Údaje o doprave tovaru ***-->
    <Transportation>
        <!--Číslo zásielky(pri použití prepravnej spoločnosti a sledovaní zásielky).-->
        <TrackingNumber>' . be_esc_xml($TransportTrackingNumber) . '</TrackingNumber>
        <!--Evidenčná značka vozidla, na ktorom sa bude vykonávať preprava tovaru.-->
        <TransportationEvidenceNumber>' . be_esc_xml($TransportTransportationEvidenceNumber) . '</TransportationEvidenceNumber>
        <!--Typ (spôsob) dopravy, napr. kuriérska služba, osobný odber a iné.-->
        <TransportationTechnique>' . be_esc_xml($TransportTransportationTechnique) . '</TransportationTechnique>
        <!--Druh vozidla prevážajúci tovar.-->
        <TransportationType>' . be_esc_xml($TransportTransportationType) . '</TransportationType>
        <!--Dátum nakládky tovaru.-->
        <LoadingDate></LoadingDate>
        <!--Poznámka pre nakládku.-->
        <LoadingNotice></LoadingNotice>
        <!--Názov miesta nakládky tovaru.-->
        <LoadingPlace_Name></LoadingPlace_Name>
        <!--Ulica miesta nakládky tovaru.-->
        <LoadingPlace_Street></LoadingPlace_Street>
        <!--Obec miesta nakládky tovaru-->
        <LoadingPlace_City></LoadingPlace_City>
        <!--PSČ Obce (miesta) nakládky tovaru-->
        <LoadingPlace_PostCode></LoadingPlace_PostCode>
        <!--Krajina (štát) miesta nakládky tovaru.-->
        <LoadingPlace_Country></LoadingPlace_Country>
        <!--GPS koordináty miesta nakládky tovaru.-->
        <LoadingPlace_GPS></LoadingPlace_GPS>
        <!--Miesto zaclenia tovaru.-->
        <LoadingPlace_Customs></LoadingPlace_Customs>
        <!--Dátum vykládky tovaru.-->
        <UnloadingDate></UnloadingDate>
        <!--Poznámka pre vykládku tovaru.-->
        <UnloadingNotice></UnloadingNotice>
        <!--Názov miesta vykládky (dodania) tovaru.-->
        <UnloadingPlace_Name></UnloadingPlace_Name>
        <!--Ulica miesta vykládky tovaru.-->
        <UnloadingPlace_Street></UnloadingPlace_Street>
        <!--Obec (mesto) vykládky (dodania).-->
        <UnloadingPlace_City></UnloadingPlace_City>
        <!--PSČ obce (mesta) vykládky tovaru.-->
        <UnloadingPlace_PostCode></UnloadingPlace_PostCode>
        <!--Krajina (štát) miesta vykládky tovaru.-->
        <UnloadingPlace_Country></UnloadingPlace_Country>
        <!--GPS koordináty miesta vykládky tovaru.-->
        <UnloadingPlace_GPS></UnloadingPlace_GPS>
        <!--Miesto vyclenia tovaru.-->
        <UnloadingPlace_Customs></UnloadingPlace_Customs>
    </Transportation>
    <!--*** Údaje o príznakoch exportu/importu údajov ***-->
    <Transport_Data>
        <TransportData_Flag_Export>0</TransportData_Flag_Export>
        <TransportData_Flag_Import>5</TransportData_Flag_Import>
        <TransportData_SourceID>0</TransportData_SourceID>
        <TransportData_SourceRecordIDNum>23</TransportData_SourceRecordIDNum>
    </Transport_Data>
    <!--*** História zmien stavov objednávky ***-->
    <HistoryStates />
</Record>';
    }

    $returnstring = '<?xml version="1.0" encoding="utf-8"?>
     <OBERON>
         <Header>
             <DocumentType>101</DocumentType>
             <DocumentTypeText>Export/import dokumentu (faktúra, objednávka, výdajka, príjemka ...)</DocumentTypeText>
             <DocumentDateTime>' . be_esc_xml($DocumentTime) . '</DocumentDateTime>
             <CompanyName>' . be_esc_xml($CompanyName) . '</CompanyName>
             <User>' . be_esc_xml($User) . '</User>
             <OBERONVersion>' . be_esc_xml($OBERONversion) . '</OBERONVersion>
             <OBERONVersionDB>' . be_esc_xml($OBERONversionDB) . '</OBERONVersionDB>
             <Description></Description>
         </Header>
         <Data>
             <!--Objednávky prijaté-->
             <OrdersReceived>' . $return . '</OrdersReceived>
             </Data>
         </OBERON>';
    return $returnstring;
}
