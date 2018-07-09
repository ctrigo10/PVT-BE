<?php

namespace Muserpol\Http\Controllers;

use Muserpol\RetirementFundCertification;
use Illuminate\Http\Request;


use Muserpol\Models\Affiliate;
use Muserpol\Models\ProcedureRequirement;
use Muserpol\Models\ProcedureModality;
use Muserpol\Models\Kinship;
use Muserpol\Models\City;
use Muserpol\Models\RetirementFund\RetirementFund;
use Muserpol\Models\RetirementFund\RetFunSubmittedDocument;
use Muserpol\Models\RetirementFund\RetFunBeneficiary;
use Muserpol\Models\RetirementFund\RetFunAddressBeneficiary;
use Muserpol\Models\RetirementFund\RetFunAdvisor;
use Muserpol\Models\RetirementFund\RetFunIncrement;
use Muserpol\Models\RetirementFund\RetFunProcedure;
use Session;
use Auth;
use DB;
use Validator;
use Muserpol\Models\Address;
use Muserpol\Models\Spouse;
use Muserpol\Models\RetirementFund\RetFunLegalGuardian;
use Muserpol\Models\RetirementFund\RetFunAdvisorBeneficiary;
use Muserpol\Models\RetirementFund\RetFunLegalGuardianBeneficiary;
use Muserpol\Models\AffiliateFolder;
use DateTime;
use Muserpol\User;
use Carbon\Carbon;
use Muserpol\Helpers\Util;
use Muserpol\Models\Voucher;
use Muserpol\Models\VoucherType;
use Muserpol\Models\Contribution\ContributionCommitment;
use Muserpol\Models\Contribution\Contribution;
use Muserpol\Models\RetirementFund\RetFunCorrelative;
use Muserpol\Models\InfoLoan;

class RetirementFundCertificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \Muserpol\RetirementFundCertification  $retirementFundCertification
     * @return \Illuminate\Http\Response
     */
    public function show(RetirementFundCertification $retirementFundCertification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Muserpol\RetirementFundCertification  $retirementFundCertification
     * @return \Illuminate\Http\Response
     */
    public function edit(RetirementFundCertification $retirementFundCertification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Muserpol\RetirementFundCertification  $retirementFundCertification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RetirementFundCertification $retirementFundCertification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Muserpol\RetirementFundCertification  $retirementFundCertification
     * @return \Illuminate\Http\Response
     */
    public function destroy(RetirementFundCertification $retirementFundCertification)
    {
        //
    }
    public function printReception($id)
    {
        $retirement_fund = RetirementFund::find($id);
        $affiliate = $retirement_fund->affiliate;
        $degree = $affiliate->degree;
        $institution = 'MUTUAL DE SERVICIOS AL POLICÍA "MUSERPOL"';
        $direction = "DIRECCIÓN DE BENEFICIOS ECONÓMICOS";
        $modality = $retirement_fund->procedure_modality->name;
        $unit = "UNIDAD DE OTORGACIÓN DE FONDO DE RETIRO POLICIAL, CUOTA MORTUORIA Y AUXILIO MORTUORIO";
        $title = ($retirement_fund->procedure_modality->id == 1 || $retirement_fund->procedure_modality->id == 2 ) ? "REQUISITOS DEL PAGO GLOBAL DE APORTES – " . mb_strtoupper($modality)  : "REQUISITOS DEL BENEFICIO FONDO DE RETIRO – " . mb_strtoupper($modality);
        $number = $retirement_fund->code;
        $user = Auth::user();//agregar cuando haya roles
        $username = Auth::user()->username;//agregar cuando haya roles
        $date = Util::getStringDate($retirement_fund->reception_date);
        $submitted_documents = RetFunSubmittedDocument::leftJoin('procedure_requirements', 'procedure_requirements.id', '=', 'ret_fun_submitted_documents.procedure_requirement_id')->where('retirement_fund_id', $retirement_fund->id)->orderBy('procedure_requirements.number', 'asc')->get();

        #todo
        /*
            add support utf-8
        */
        $bar_code = \DNS2D::getBarcodePNG(($retirement_fund->getBasicInfoCode()['code']."\n\n". $retirement_fund->getBasicInfoCode()['hash']), "PDF417", 100, 33, array(1, 1, 1));
        $applicant = RetFunBeneficiary::where('type', 'S')->where('retirement_fund_id', $retirement_fund->id)->first();
        //return view('ret_fun.print.reception', compact('title','usuario','fec_emi','name','ci','expedido'));

       // $pdf = view('print_global.reception', compact('title','usuario','fec_emi','name','ci','expedido'));       
    //    return view('ret_fun.print.reception',compact('user','title','institution', 'direction', 'unit','username','date','modality','applicant', 'affiliate', 'degree','submitted_documents','header','number'));
        $pdftitle = "RECEPCIÓN - " . $title;
        $namepdf = Util::getPDFName($pdftitle, $applicant);
        $footerHtml = view()->make('ret_fun.print.footer', ['bar_code'=>$bar_code])->render();

        $pages = [];
        for ($i = 1; $i <= 2; $i++) {
            $pages[] = \View::make('ret_fun.print.reception', compact('bar_code', 'user', 'title', 'institution', 'direction', 'unit', 'username', 'date', 'modality', 'applicant', 'affiliate', 'degree', 'submitted_documents', 'header', 'number'))->render();
        }
        $pdf = \App::make('snappy.pdf.wrapper');
        $pdf->loadHTML($pages);
        return $pdf->setPaper('letter')
                   ->setOption('encoding', 'utf-8')
                //    ->setOption('margin-top', '20mm')
                   ->setOption('margin-bottom', '15mm')
                //    ->setOption('margin-left', '25mm')
                //    ->setOption('margin-right', '15mm')
                    //->setOption('footer-right', 'PLATAFORMA VIRTUAL DE TRÁMITES - MUSERPOL')
                //    ->setOption('footer-right', 'Pagina [page] de [toPage]')
                   ->setOption('footer-html', $footerHtml)
                   ->stream("$namepdf");
    }
    public function printFile($id)
    {
        $affiliate = Affiliate::find($id);
        $retirement_fund = RetirementFund::where('affiliate_id', $affiliate->id)->get()->last();
        $number = Util::getNextAreaCode($retirement_fund->id);        
        
        $date = Util::getStringDate($retirement_fund->reception_date);
        $title = "CERTIFICACIÓN DE ARCHIVO – " . strtoupper($retirement_fund->procedure_modality->name ?? 'ERROR');
        $username = Auth::user()->username;//agregar cuando haya roles        
        $affiliate_folders = AffiliateFolder::where('affiliate_id', $affiliate->id)->get();
        $applicant = RetFunBeneficiary::where('type', 'S')->where('retirement_fund_id', $retirement_fund->id)->first();
        $cite = RetFunIncrement::getIncrement(Session::get('rol_id'), $retirement_fund->id);
        $subtitle = $cite;
        $pdftitle = "Certificación de Archivo";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);
        $user = Auth::user();
        $footerHtml = view()->make('ret_fun.print.footer', ['bar_code'=>$this->generateBarCode($retirement_fund)])->render();
        return \PDF::loadView(
                'ret_fun.print.file_certification', 
                compact(
                    'date', 
                    'subtitle', 
                    'username', 
                    'cite', 
                    'title', 
                    'number', 
                    'retirement_fund', 
                    'affiliate', 
                    'affiliate_folders', 
                    'applicant',
                    'user'))
                ->setPaper('letter')
                ->setOption('encoding', 'utf-8')
                ->setOption('margin-bottom', '15mm')
                //->setOption('footer-right', 'Pagina [page] de [toPage]')
                ->setOption('footer-right', 'PLATAFORMA VIRTUAL DE TRÁMITES - MUSERPOL')
                ->setOption('footer-html', $footerHtml)
                ->stream("$namepdf");

    }
    public function printLegalReview($id)
    {
        $retirement_fund = RetirementFund::find($id);
        $date = Util::getStringDate($retirement_fund->reception_date);
        //$title = "CERTIFICACION DE ARCHIVO – ".strtoupper($retirement_fund->procedure_modality->name);       
        $title = "CERTIFICACI&Oacute;N DE DOCUMENTACI&Oacute;N PRESENTADA Y REVISADA";
        $submitted_documents = RetFunSubmittedDocument::where('retirement_fund_id', $id)->orderBy('procedure_requirement_id', 'ASC')->get();
        $username = Auth::user()->username;//agregar cuando haya roles
        $date = Util::getStringDate($retirement_fund->reception_date);
        $affiliate = $retirement_fund->affiliate;        
        $number = Util::getNextAreaCode($retirement_fund->id);
//        $data = [
//            'retirement_fund'   =>  $retirement_fund,
//            'submitted_documents'   => $submitted_documents,            
//        ];
        $footerHtml = view()->make('ret_fun.print.footer', ['bar_code'=>$this->generateBarCode($retirement_fund)])->render();
        $cite = $number;//RetFunIncrement::getIncrement(Session::get('rol_id'), $retirement_fund->id);
        $subtitle = $cite;
        $pdftitle = "Revision Legal";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);
        $user = Auth::user();
        return \PDF::loadView('ret_fun.print.legal_certification', 
            compact(
                'date', 
                'subtitle', 
                'username', 
                'title', 
                'number', 
                'retirement_fund', 
                'affiliate', 
                'submitted_documents',
                'user'))
            ->setPaper('letter')
            ->setOption('encoding', 'utf-8')
            //->setOption('footer-right', 'Pagina [page] de [toPage]')
            //->setOption('footer-right', 'PLATAFORMA VIRTUAL DE TRÁMITES - MUSERPOL')
            ->setOption('footer-html', $footerHtml)
            ->stream("$namepdf");
    }
    public function printBeneficiariesQualification($id)
    {
        $retirement_fund = RetirementFund::find($id);
        $date = date('d/m/Y');
        $title = $retirement_fund->procedure_modality->procedure_type->module->name;
        $username = Auth::user()->username;//agregar cuando haya roles

        $affiliate = $retirement_fund->affiliate;
        $applicant = $retirement_fund->ret_fun_beneficiaries()->where('type', 'S')->with('kinship')->first();
        $beneficiaries = $retirement_fund->ret_fun_beneficiaries;

        $number = $retirement_fund->code;
        $pdftitle = "Calificacion";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);

        $data = [
            'date' => $date,
            'username' => $username,
            'title' => $title,
            'number' => $number,

            'affiliate' => $affiliate,
            'applicant' => $applicant,
            'beneficiaries' => $beneficiaries,
            'retirement_fund' => $retirement_fund,
        ];
        // return view('ret_fun.print.beneficiaries_qualification', $data);
        return \PDF::loadView('ret_fun.print.beneficiaries_qualification', $data)->setPaper('letter')->setOption('encoding', 'utf-8')->setOption('footer-right', 'Pagina [page] de [toPage]')->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')->stream("$namepdf");
    }
    public function printDataQualification($id)
    {
        $retirement_fund = RetirementFund::find($id);
        $date = date('d/m/Y');
        $title = $retirement_fund->procedure_modality->procedure_type->module->name;
        $username = Auth::user()->username;//agregar cuando haya roles
        $affiliate = $retirement_fund->affiliate;
        $applicant = $retirement_fund->ret_fun_beneficiaries()->where('type', 'S')->with('kinship')->first();

        $beneficiaries = $retirement_fund->ret_fun_beneficiaries;
        $number = $retirement_fund->code;
        $pdftitle = "Calificacion";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);

        $data = [
            'date' => $date,
            'username' => $username,
            'title' => $title,
            'number' => $number,

            'affiliate' => $affiliate,
            'applicant' => $applicant,
            'beneficiaries' => $beneficiaries,
            'retirement_fund' => $retirement_fund,
        ];
        // return view('ret_fun.print.beneficiaries_qualification', $data);
        return \PDF::loadView('ret_fun.print.qualification_step_data', $data)->setPaper('letter')->setOption('encoding', 'utf-8')->setOption('footer-right', 'Pagina [page] de [toPage]')->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')->stream("$namepdf");
    }
    public function printDataQualificationAvailability($id)
    {
        $retirement_fund = RetirementFund::find($id);
        $date = date('d/m/Y');
        // $title = $retirement_fund->procedure_modality->procedure_type->module->name;
        $title = "devolución de aportes en disponibilidad ";
        $username = Auth::user()->username;//agregar cuando haya roles
        $affiliate = $retirement_fund->affiliate;
        $applicant = $retirement_fund->ret_fun_beneficiaries()->where('type', 'S')->with('kinship')->first();

        $beneficiaries = $retirement_fund->ret_fun_beneficiaries;
        $number = $retirement_fund->code;
        $pdftitle = "Calificacion";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);

        $data = [
            'date' => $date,
            'username' => $username,
            'title' => $title,
            'number' => $number,

            'affiliate' => $affiliate,
            'applicant' => $applicant,
            'beneficiaries' => $beneficiaries,
            'retirement_fund' => $retirement_fund,
        ];
        // return view('ret_fun.print.beneficiaries_qualification', $data);
        return \PDF::loadView('ret_fun.print.qualification_data_availability', $data)->setPaper('letter')->setOption('encoding', 'utf-8')->setOption('footer-right', 'Pagina [page] de [toPage]')->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')->stream("$namepdf");
    }
    public function printDataQualificationRetFunAvailability($id)
    {
        $retirement_fund = RetirementFund::find($id);
        $date = date('d/m/Y');
        // $title = $retirement_fund->procedure_modality->procedure_type->module->name;
        $title = "fondo de retiro y disponibilidad ";
        $username = Auth::user()->username;//agregar cuando haya roles
        $affiliate = $retirement_fund->affiliate;
        $applicant = $retirement_fund->ret_fun_beneficiaries()->where('type', 'S')->with('kinship')->first();
        $beneficiaries = $retirement_fund->ret_fun_beneficiaries;
        $number = $retirement_fund->code;
        $pdftitle = "Calificacion";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);

        $data = [
            'date' => $date,
            'username' => $username,
            'title' => $title,
            'number' => $number,

            'affiliate' => $affiliate,
            'applicant' => $applicant,
            'beneficiaries' => $beneficiaries,
            'retirement_fund' => $retirement_fund,
        ];
        // return view('ret_fun.print.beneficiaries_qualification', $data);
        return \PDF::loadView('ret_fun.print.qualification_data_ret_fun_availability', $data)->setPaper('letter')->setOption('encoding', 'utf-8')->setOption('footer-right', 'Pagina [page] de [toPage]')->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')->stream("$namepdf");
    }
    public function printRetFunCommitmentLetter($id)
    {
        $affiliate = Affiliate::find($id);
        $commitment = ContributionCommitment::where('affiliate_id', $affiliate->id)->first();
        $date = Util::getStringDate(date('Y-m-d'));
        $username = Auth::user()->username;//agregar cuando haya roles
        $city = Auth::user()->city->name;
        $glosa = "No corresponde";
        if ($affiliate->affiliate_state->name == "Baja Temporal") {
            $title = "COMPROMISO DE PAGO - APORTE VOLUNTARIO SUSPENDIDOS TEMPORALMENTE DE FUNCIONES POR PROCESOS DISCIPLINARIOS";
            $glosa = 'Suspendido temporalmente de funciones por procesos disciplinarios, figurando en planilla de haberes con ítem "0".';
            $glosa_pago = "de mi última boleta de pago efectivamente percibida";
        } else {
            $title = 'COMPROMISO DE PAGO - APORTE VOLUNTARIO COMISIÓN DE SERVICIO ÍTEM "0" O AGREGADOS POLICIALES EN EL EXTERIOR DEL PAÍS';
            $glosa_pago = "de mi total ganado mensual (sin descuentos)";
            if ($affiliate->affiliate_state->name == "Comisión") {
                $glosa = 'Comisión de Servicio Ítem "0".';
            } else {
                if ($affiliate->affiliate_state->name == "Agregado Policial") {
                    $glosa = "Agregado Policial en el exterior del país.";
                }
            }
        }
        $pdftitle = "Carta de Compromiso de Fondo de Retiro";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);
        // return view('ret_fun.print.beneficiaries_qualification', compact('date','subtitle','username','title','number','retirement_fund','affiliate','submitted_documents'));
        return \PDF::loadView(
            'ret_fun.print.ret_fun_commitment_letter',
            compact(
                'date',
                'username',
                'title',
                'affiliate',
                'glosa',
                'city',
                'glosa_pago',
                'commitment'
            )
        )
            ->setPaper('letter')
            ->setOption('encoding', 'utf-8')
            ->setOption('footer-right', 'Pagina [page] de [toPage]')
            ->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')
            ->stream("$namepdf");
    }

    public function printVoucher(Request $request, $affiliate_id, $voucher_id)
    {
        $affiliate = Affiliate::find($affiliate_id);
        $voucher = Voucher::find($voucher_id);
        $contributions = [];
        $total_literal = Util::convertir($voucher->total);
        $payment_date = Util::getStringDate($voucher->payment_date);
        $date = Util::getStringDate(date('Y-m-d'));
        $title = "RECIBO";
        $subtitle = "FONDO DE RETIRO Y CUOTA MORTUORIA";
        $username = Auth::user()->username;//agregar cuando haya roles
        $name_user_complet = Auth::user()->first_name . " " . Auth::user()->last_name;
        $number = $voucher->code;
        $descripcion = VoucherType::where('id', $voucher->voucher_type_id)->first();
        $beneficiary = $affiliate;
        $contributions = json_decode($request->contributions);
        $pdftitle = "Comprobante";
        $namepdf = Util::getPDFName($pdftitle, $beneficiary);
        $util = new Util();
        // return view('ret_fun.print.beneficiaries_qualification', compact('date','subtitle','username','title','number','retirement_fund','affiliate','submitted_documents'));
        return \PDF::loadView(
            'ret_fun.print.voucher_contribution',
            compact(
                'date',
                'username',
                'title',
                'subtitle',
                'affiliate',
                'submitted_documents',
                'beneficiary',
                'contributions',
                'number',
                'voucher',
                'util',
                'descripcion',
                'payment_date',
                'total_literal',
                'name_user_complet'
            )
        )
            ->setPaper('letter')
            ->setOption('encoding', 'utf-8')
            ->setOption('footer-right', 'Pagina [page] de [toPage]')
            ->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')
            ->stream("$namepdf");
    }

    public function printDirectContributionQuote(Request $request)
    {
        $contributions = json_decode($request->contributions);
        $total = $request->total;
        $total_literal = Util::convertir($total);
        $affiliate = Affiliate::find($request->affiliate_id);
        $date = Util::getStringDate(date('Y-m-d'));
        $title = "PAGO DE APORTES VOLUNTARIOS APORTE DIRECTO VIUDAS EFECTIVO";
        $username = Auth::user()->username;//agregar cuando haya roles
        $name_user_complet = Auth::user()->first_name . " " . Auth::user()->last_name;
        $detail = "Pago de aporte directo";
        $beneficiary = $affiliate;
        $name_beneficiary_complet = Util::fullName($beneficiary);
        $pdftitle = "Comprobante";
        $namepdf = Util::getPDFName($pdftitle, $beneficiary);
        $util = new Util();
        return \PDF::loadView(
            'ret_fun.print.affiliate_contribution',
            compact(
                'date',
                'username',
                'title',
                'number',
                'beneficiary',
                'contributions',
                'total',
                'total_literal',
                'detail',
                'util',
                'name_user_complet',
                'name_beneficiary_complet'
            )
        )
            ->setPaper('letter')
            ->setOption('encoding', 'utf-8')
            ->setOption('footer-right', 'Pagina [page] de [toPage]')
            ->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')
            ->stream("$namepdf");
    }

    private function generateBarCode($retirement_fund){
        $bar_code = \DNS2D::getBarcodePNG((
                        $retirement_fund->getBasicInfoCode()['code']."\n\n".$retirement_fund->getBasicInfoCode()['hash']), 
                        "PDF417", 
                        100, 
                        33, 
                        array(1, 1, 1)
                    );
        return $bar_code;
    }

    public function printCertification($id)
    {
        $retirement_fund = RetirementFund::find($id);
        $affiliate = $retirement_fund->affiliate;
        $servicio = ContributionType::where('name','=','Período reconocido por comando')->first();
        $item_cero = ContributionType::where('name','=','Período en item 0 Con Aporte')->first();
        $quantity = Util::getRetFunCurrentProcedure()->contributions_number;
        $contributions_sixty = Contribution::where('affiliate_id', $affiliate->id)
                        ->where(function ($query) use ($servicio,$item_cero){
                            $query->where('contribution_type_id',$servicio->id)
                            ->orWhere('contribution_type_id',$item_cero->id);
                        })
                        ->orderBy('month_year','desc')
                        ->take($quantity)
                        ->get();                                          
        $contributions = $contributions_sixty->sortBy('month_year')->all();                           
        $reimbursements = Reimbursement::where('affiliate_id', $affiliate->id)
                        ->orderBy('month_year')
                        ->get();
        $institution = 'MUTUAL DE SERVICIOS AL POLICÍA "MUSERPOL"';
        $direction = "DIRECCIÓN DE BENEFICIOS ECONÓMICOS";
        $unit = "UNIDAD DE OTORGACIÓN DE FONDO DE RETIRO POLICIAL, CUOTA MORTUORIA Y AUXILIO MORTUORIO";
        $title = "CERTIFICACION DE APORTES";
        $subtitle ="Cuenta Individual";
        //$number = $retirement_fund->code;
        $number = Util::getNextAreaCode($retirement_fund->id);
        $date = Util::getStringDate($retirement_fund->reception_date);        
        $degree = Degree::find($affiliate->degree_id);
        $exp = City::find($affiliate->city_identity_card_id);
        $exp = ($exp==Null)? "-": $exp->first_shortened;
        $dateac = Carbon::now()->format('d/m/Y');
        $place = City::find($retirement_fund->city_start_id); 
        $num=0;       
        $username = Auth::user()->username;
        $pdftitle = "Cuentas Individuales";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);       
        return \PDF::loadView('contribution.print.certification_contribution', compact('num','subtitle','place','retirement_fund','reimbursements','dateac','exp','degree','contributions','affiliate','title', 'username','institution', 'direction', 'unit', 'date', 'header', 'number'))->setPaper('letter')->setOption('encoding', 'utf-8')->setOption('footer-right', 'Pagina [page] de [toPage]')->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')->stream("$namepdf");
    }
    public function printCertificationAvailability($id)
    {
        $retirement_fund = RetirementFund::find($id);
        $affiliate = $retirement_fund->affiliate;
        $disponibilidad = ContributionType::where('name','=','Disponibilidad')->first();
        $contributions = Contribution::where('affiliate_id', $affiliate->id)
                        ->orderBy('month_year')
                        ->get();
        $reimbursements = Reimbursement::where('affiliate_id', $affiliate->id)
                        ->orderBy('month_year')
                        ->get();                          
        $institution = 'MUTUAL DE SERVICIOS AL POLICÍA "MUSERPOL"';
        $direction = "DIRECCIÓN DE BENEFICIOS ECONÓMICOS";
        $unit = "UNIDAD DE OTORGACIÓN DE FONDO DE RETIRO POLICIAL, CUOTA MORTUORIA Y AUXILIO MORTUORIO";
        $title = "CERTIFICACION DE APORTES EN DISPONIBILIDAD";
        $subtitle ="Cuenta Individual";
        $number = $retirement_fund->code;
        $date = Util::getStringDate($retirement_fund->reception_date);
        $degree = Degree::find($affiliate->degree_id);
        $exp = City::find($affiliate->city_identity_card_id);
        $exp = ($exp==Null)? "-": $exp->first_shortened;
        $dateac = Carbon::now()->format('d/m/Y');
        $place = City::find($retirement_fund->city_start_id); 
        $num=0;             
        $username = Auth::user()->username;
        $pdftitle = "Cuentas Individuales";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);
        
        //total de los aportes
        $aporte=$retirement_fund->subtotal_availability;
       
        return \PDF::loadView('contribution.print.certification_availability', compact('num','disponibilidad','aporte','subtitle','place','retirement_fund','reimbursements','dateac','exp','degree','contributions','affiliate','title', 'username','institution', 'direction', 'unit', 'date','header', 'number'))->setPaper('letter')->setOption('encoding', 'utf-8')->setOption('footer-right', 'Pagina [page] de [toPage]')->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')->stream("$namepdf");
    }
    public function printCertificationItem0($id)
    {
        $retirement_fund = RetirementFund::find($id);
        $affiliate = $retirement_fund->affiliate;
        $itemcero = ContributionType::where('name','=','Período en item 0 Con Aporte')->first();
        $itemcero_sin_aporte = ContributionType::where('name','=','Período en item 0 Sin Aporte')->first();
        $contributions = Contribution::where('affiliate_id', $affiliate->id)
                        ->orderBy('month_year')
                        ->get();
        $reimbursements = Reimbursement::where('affiliate_id', $affiliate->id)
                        ->orderBy('month_year')
                        ->get();
        $institution = 'MUTUAL DE SERVICIOS AL POLICÍA "MUSERPOL"';
        $direction = "DIRECCIÓN DE BENEFICIOS ECONÓMICOS";
        $unit = "UNIDAD DE OTORGACIÓN DE FONDO DE RETIRO POLICIAL, CUOTA MORTUORIA Y AUXILIO MORTUORIO";
        $title = "CERTIFICACION DE CUENTAS INDIVIDUALES ITEM 0";
        $subtitle = "Cuenta Individual";
        $number = $retirement_fund->code;
        $date = Util::getStringDate($retirement_fund->reception_date);
        $degree = Degree::find($affiliate->degree_id);
        $exp = City::find($affiliate->city_identity_card_id);
        $exp = ($exp==Null)? "-": $exp->first_shortened;
        $dateac = Carbon::now()->format('d/m/Y');
        $place = City::find($retirement_fund->city_start_id);              
        $username = Auth::user()->username;
        $pdftitle = "Cuentas Individuales";
        $namepdf = Util::getPDFName($pdftitle, $affiliate);
        return \PDF::loadView('contribution.print.certification_item0', compact('itemcero','itemcero_sin_aporte','subtitle','place','retirement_fund','reimbursements','dateac','exp','degree','contributions','affiliate','title', 'username','institution', 'direction', 'unit', 'date','header', 'number'))->setPaper('letter')->setOption('encoding', 'utf-8')->setOption('footer-right', 'Pagina [page] de [toPage]')->setOption('footer-left', 'PLATAFORMA VIRTUAL DE LA MUSERPOL - 2018')->stream("$namepdf");
    } 

    public function printSecurity(){

    }
    public function printLegalDictum($id){
        $retirement_fund = RetirementFund::find($id);
        $applicant = RetFunBeneficiary::where('type', 'S')->where('retirement_fund_id', $retirement_fund->id)->first();
        $beneficiaries = RetFunBeneficiary::where('retirement_fund_id',$retirement_fund->id)->get();

        /** PERSON DATA */
        $person = "";
        $affiliate = Affiliate::find($retirement_fund->affiliate_id);
        $ret_fun_beneficiary = RetFunLegalGuardianBeneficiary::where('ret_fun_beneficiary_id',$applicant->id)->first(); //revisar por si el primero no es solicitante
        if(isset($ret_fun_beneficiary->id)){
            $legal_guardian = RetFunLegalGuardian::where('id',$ret_fun_beneficiary->ret_fun_legal_guardian_id)->first();
            //return $legal_guardian;
            $person .= "Mediante Escritura Pública sobre Testimonio de Poder especial, amplio y suficiente N°".  $legal_guardian->number_authority ." de fecha FECHA otorgado al Sr. ".Util::fullName($legal_guardian)." con C.I. N° ".$legal_guardian->identity_card." representa legalmente al ";
        }
        else
        {
            $person .= "El ";
        }
        $person .= "señor ". $affiliate->fullNameWithDegree() ." con C.I. N° ". $affiliate->ciWithExt() .", como TITULAR del beneficio del Fondo de Retiro Policial Solidario en su modalidad de <b>". strtoupper($retirement_fund->procedure_modality->name) ."</b>, presenta la documentación para la otorgación del beneficio en fecha ". Util::getStringDate($retirement_fund->reception_date) .", a lo cual considera lo siguiente:";
        /** END PERSON DATA */

        /** LAW DATA */
        $law = "Conforme normativa, el trámite N° ".$retirement_fund->code." de la Regional ".$retirement_fund->city_start->name." es ingresado por Ventanilla
        de Atención al Afiliado de la Unidad de Otorgación del Fondo de Retiro Policial, Cuota y Auxilio
        Mortuorio; verificados los requisitos y la documentación presentada por la parte solicitante
        según lo señalado el Art. 41 inciso a) del Reglamento de Fondo de Retiro Policial Solidario
        aprobado mediante Resolución de Directorio N° 31/2017 en fecha 24 de agosto de 2017 y
        modificado mediante Resolución de Directorio N° 36/2017 en fecha 20 de septiembre de 2017,
        y conforme el Art. 45 de referido Reglamento, se detalla la documentación como resultado de
        la aplicación de la base técnica-legal del Estudio Matemático Actuarial 2016-2020, generada y
        adjuntada al expediente por los funcionarios de la Unidad de Otorgación del Fondo de Retiro
        Policial, Cuota y Auxilio Mortuorio, según correspondan las funciones, detallando lo siguiente:<br><br>";
        /** END LAW DATA */

        $body = "";        

        ///---FILE---///
        $body_file = "";    
        $file_id = 20;
        $file = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$file_id)->first();        
        $body_file .= "Que, mediante Certificación ". $file->code .", de fecha ". Util::getStringDate($file->date) .", de Archivo de Beneficios Económicos, se establece que el trámite signado con el N° ". $retirement_fund->code." ";
        $folders=AffiliateFolder::where('affiliate_id',$affiliate->id)->get();
        if($folders->count()>0)
            $body_file .= "si tiene expediente del referido titular y cuenta con anticipo de Fondo de Retiro Policial.";
        else 
            $body_file .= "no tiene expediente del referido titular.";
            
        ///---ENDIFLE--////

        /////----FINANCE----///        
        $discount = $retirement_fund->discount_types();
        $finance = $discount->where('discount_type_id','1')->first();
        $body_finance = "";
        $body_finance = "Que, mediante nota de respuesta de la Dirección Administrativa Financiera con Cite: ".$finance->pivot->code ." de fecha ". Util::getStringDate($finance->pivot->date).",";
        if(isset($finance->id)){
            $body_finance .= "se evidencia anticipo por concepto de Fondo de Retiro Policial en el monto de ".Util::formatMoney($finance->pivot->amount)." (".Util::convertir($finance->pivot->amount).").";
        }
        else{
            $body_finance .= "no se evidencia pagos o anticipos por concepto de Fondo de Retiro Policial.";
        }                         
        /////----END FINANCE---////

        ////-----LEGAL REVIEW ----////      
        $body_legal_review   = "";
        $legal_review_id = 21;
        $legal_review = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$file_id)->first();
        $body_legal_review .= "Que, mediante Certificación N° ".$legal_review->code." del Área Legal de la Unidad de Otorgación del Fondo de Retiro Policial Solidario, Cuota y Auxilio Mortuorio, de fecha ".Util::getStringDate($legal_review->date).", fue verificada y validada la documentación presentada por el titular el trámite signado con el N° ".$retirement_fund->code.".";
        /////-----END LEGAL REVIEW----///
        
        ///------ INDIVIDUAL ACCCOUTNS ------////    
        $body_accounts = "";           
        $accounts_id = 22;
        $accounts = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$file_id)->first();
        $availability_code = 10;
        $availability_number_contributions = Contribution::where('affiliate_id',$affiliate->id)->where('contribution_type_id',$availability_code)->count();
        $body_accounts = "Que, mediante Certificación de Aportes N° ".$accounts->code." de Cuentas Individuales de la Unidad de Otorgación del Fondo de Retiro Policial Solidario, Cuota y Auxilio Mortuorio, de fecha ". Util::getStringDate($accounts->date) .", se verificó los últimos "."60"." aportes antes de su destino a disponibilidad de las letras (reserva activa) del titular. Mediante Certificación de Aportes en Disponibilidad N° ".$accounts->code." de Cuentas Individuales de la Unidad de Otorgación del Fondo de Retiro Policial Solidario, Cuota y Auxilio Mortuorio, de fecha ". Util::getStringDate($accounts->date) .", durante la permanencia en la reserva activa se verificó ". $availability_number_contributions ." aportes en disponibilidad.";

        ////------- INDIVIDUAL ACCOUTNS ------////

        //----- QUALIFICATION -----////      
        $body_qualification = "";
        $qualification_id = 23;
        $qualification = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$qualification_id)->first();
        $months  = $affiliate->getTotalQuotes();        
        $body_qualification .= "Que, mediante Calificación Fondo de Retiro Policial Solidario N° ".$qualification->code." de la Encargada de Calificación de la Unidad de Otorgación del Fondo de Retiro Policial Solidario, Cuota y Auxilio Mortuorio, de fecha ". Util::getStringDate($qualification->date) .", se realizó el cálculo por el periodo de ". (int)($months/12) ." y ". ($months%12) .", determinando el beneficio de Fondo de Retiro Policial Solidario por Jubilación de ". Util::formatMoney($retirement_fund->total_ret_fun) ." (". Util::convertir($retirement_fund->total_ret_fun) .")".Util::getDiscountCombinations($retirement_fund->id);
        ///----- END QUALIFICATION ----////
        

        ////----- DUE -----////
        
        $discounts = $retirement_fund->discount_types();
        $discount = $discounts->where('discount_type_id','3')->first();
        $loans = InfoLoan::where('affiliate_id',$affiliate->id)->get();
        $body_due = "Que, mediante nota ".$discount->pivot->note. "de la fecha ".Util::getStringDate($discount->pivot->date). "la Dirección de Estrategias Sociales e Inversiones, certificaque el titular no cuenta con deuda en curso de pago a MUSERPOL y por concepto de garantes adeuda a los señores ";        
        $num_loans = $loans->count();        
        $i=0;
        foreach($loans as $loan){
            $i++;
            if($i!=1)
            {
                if($num_loans-$i==0)
                    $body_due .= " y ";
                else
                    $body_due .= ", ";
            }
            $body_due.= $loan->affiliate_guarantor->fullName()." con C.I. N° ".$loan->affiliate_guarantor->identity_card." en la suma de Bs ".Util::formatMoney($loan->amount)." (".Util::convertir($discount->pivot->amount);
        }
        $body_due .= " en conformidad al contrato de préstamo Nro. ".$discount->pivot->code."."; 
        


        ///-----END DUE----///

        ///------ PAYMENT ------////
        $payment = "";
        $discounts = $retirement_fund->discount_types(); //DiscountType::where('retirement_fund_id',$retirement_fund->id)->orderBy('discount_type_id','ASC')->get();                
        $loans = InfoLoan::where('affiliate_id',$affiliate->id)->get();
        $payment = "Por consiguiente, habiendo sido remitido el presente tramite al Área Legal Unidad de
        Otorgación del Fondo de Retiro Policial Solidario, autorizado por Jefatura de la Unidad de
        Otorgación del Fondo de Retiro Policial Solidario, conforme a los Art. 2, 3, 5, 10, 26, 27, 28,
        32, 36, 37, 38, 41, 42, 44, 45, 48, 49, 50, 70, 71, 72, 73, 74 y la Disposición Transitoria
        Segunda, del Reglamento de Fondo de Retiro Policial Solidario, aprobado mediante
        Resolución de Directorio N° 31/2017 en fecha 24 de agosto de 2017 y modificado mediante
        Resolución de Directorio N° 36/2017 en fecha 20 de septiembre de 2017. Se DICTAMINA en
        merito a la documentación de respaldo contenida en el presente, ";
                
        $flagy = 0;
        if($discounts->count()>0)
            $payment .= "proceder a realizar el descuento de ";

        $discount = $discounts->where('discount_type_id','1')->first();
        
        if(isset($discount->id)){            
            $payment.="Bs ".Util::formatMoney($discount->pivot->amount)." (".Util::convertir($discount->pivot->amount).") por concepto de anticipo de Fondo de Retiro Policial de conformidad a la nota Nro. ".$discount->pivot->note_code." de fecha ".Util::getStringDate($discount->pivot->date);            
        }
        
        $discounts = $retirement_fund->discount_types();
        
        if(isset($discount->id)){
            $payment .= $this->getFlagy(3,2);
            // if($flagy == 1)
            // $body .= " y la suma de ";
            $payment.="Bs ".Util::formatMoney($discount->pivot->amount)." (".Util::convertir($discount->pivot->amount).") por concepto de saldo de deuda con la MUSERPOL de conformidad al contrato de préstamo Nro. ".$discount->code." y nota ".$discount->note_code." de fecha ".Util::getStringDate($discount->date);
        }
        //
        $discounts = $retirement_fund->discount_types();
        $discount = $discounts->where('discount_type_id','3')->first();
        $loans = InfoLoan::where('affiliate_id',$affiliate->id)->get();

        $payment.="Bs ".Util::formatMoney($discount->pivot->amount)." (".Util::convertir($discount->pivot->amount).") por concepto de garantía de préstamo a favor de";// los señores. ".$discount->code." y nota ".$discount->note_code." de fecha ".$discount->date;
        $num_loans = $loans->count();
        if($num_loans==1)
            $payment .= "l señore ";
        else
            $payment .= " los señores ";
        $i=0;
        foreach($loans as $loan){
            $i++;
            if($i!=1)
            {
                if($num_loans-$i==0)
                    $payment .= " y ";
                else
                    $payment .= ", ";
            }
            $payment.= $loan->affiliate_guarantor->fullName()." con C.I. N° ".$loan->affiliate_guarantor->identity_card." en la suma de Bs ".Util::formatMoney($loan->amount)." (".Util::convertir($discount->pivot->amount);
        }
        $payment .= " en conformidad al contrato de préstamo Nro. ".$discount->pivot->code." y la nota ".$discount->pivot->note_code." de fecha ". Util::getStringDate($retirement_fund->reception_date) ." de la Dirección de Estrategias Sociales e Inversiones. Reconocer los derechos y se otorgue el beneficio del Fondo de Retiro Policial Solidario por <b>".strtoupper($retirement_fund->procedure_modality->name)."</b> a favor de:<br><br>"; 
        $payment .= $affiliate->degree->shortened." ".$affiliate->fullName()." con C.I. N° ".$affiliate->identity_card." ".$affiliate->city_identity_card->first_shortened."., el monto de Bs ".Util::formatMoney($retirement_fund->total_ret_fun)." (".Util::convertir($retirement_fund->total_ret_fun).").";
        ///------EN  PAYMENT ------///
        $number = Util::getNextAreaCode($retirement_fund->id);
        $data = [
            'ret_fun' => $retirement_fund,
            'beneficiaries'    =>  $beneficiaries,
            'correlative'  =>  $number,
            'actual_city'  =>  Auth::user()->city->name,
            'actual_date'  =>  Util::getStringDate(date('Y-m-d')),

            'person'    =>  $person,
            'law'   =>  $law,
            'body_file'  =>  $body_file,
            'body_accounts'  =>  $body_accounts,
            'body_finance'  =>  $body_finance,
            'body_legal_review'  =>  $body_legal_review,
            'body_qualification'  =>  $body_qualification,
            'body_due'  =>  $body_due,
            'payment'   =>  $payment,
        ];

        return \PDF::loadView('ret_fun.print.legal_dictum', $data)
        ->setPaper('letter')
        ->setOption('encoding', 'utf-8')
        ->stream("dictamenLegal.pdf");      
    }

    public function printHeadshipReview($ret_fun_id){
        $retirement_fund =  RetirementFund::find($ret_fun_id);
        $affiliate = Affiliate::find($retirement_fund->affiliate_id);

        $head = "Señora Directora:<br><br>En atención a solicitud de fecha ".Util::getStringDate($retirement_fund->reception_date).", del beneficio de Fondo de Retiro Policial, la ".$affiliate->fullName()." con CI. ".$affiliate->identity_card." ".$affiliate->city_identity_card->first_shortened."., en calidad de viuda del Sr. CBO. BERNABE CARVAJAL VALENCIA con C.I. N° 4836338 LP, solicita el beneficio del Fondo de Retiro Policial Solidario por <b>".strtoupper($retirement_fund->procedure_modality->name)."</b> y en cumplimiento al numeral 8 del artículo 45 del Reglamento de Fondo de Retiro Policial Solidario, elevo el presente informe de revisión:";
        $past = "Conforme al Decreto Supremo N°1446 de 19 de diciembre de 2012, modificado por el Decreto Supremo N° 3231 de 28 de junio de 2017, referente al beneficio de Fondo de Retiro Policial en el artículo 2, (MODIFICACIONES) establece:
            <br><br>
            I. Se modifica el inciso c) del artículo 3 del Decreto Supremo N° 1446, de 19 de diciembre de 2012, con el siguiente texto: <b>“Otorgar el beneficio variable del Fondo de Retiro Policial Solidario, en el marco del principio de solidaridad”</b>
            <br><br>
            IV. Se modifica el inciso a) del Parágrafo I del artículo 14 del Decreto Supremo N° 1446, del 19 de diciembre de 2012, con el siguiente texto: <b>“a) Fondo de Retiro Policial Solidario”</b>, 
            <br><br>
            V. Se modifica el parágrafo III del artículo 14 del Decreto Supremo N° 1446, del 19 de diciembre de 2012, con el siguiente texto: <b>“III. Los beneficios señalados en el presente artículo se rigen por los principios de equidad y solidaridad, debiendo ser otorgados a todos los afiliados, aportantes de la Policía Boliviana en sus diferentes sectores y niveles sin ninguna distinción” </b>
            <br><br>
            VI. Se modifica el artículo 15 del Decreto Supremo N° 1446, del 19 de diciembre de 2012, con el siguiente texto: <b>“Articulo 15.- (FONDO DE RETIRO POLICIAL SOLIDARIO). Es el beneficio que brinda protección a los miembros del servicio activo y a sus derechohabientes, mediante el reconocimiento del pago único, con motivo y oportunidad del retiro definitivo de la actividad remunerada dependiente de la Policía Boliviana, el cual será administrado por la MUSERPOL; a ser otorgado en el marco del principio de solidaridad, cuando el retiro se produzca por: </b>
            <br><br>
            <b>a) Jubilación; b) Fallecimiento del titular; c) Retiro forzoso; d) Retiro voluntario.” </b>
            <br><br>
            Asimismo, como dispone en su Disposición Transitoria Única. – <b>“I. Los tramites ingresados y pendientes hasta la gestión 2015, serán determinados bajo los parámetros establecidos por el Estudio Matemático Actuarial 2016-2020 y pagados con los saldos acumulados hasta la fecha de publicación del presente Decreto Supremo. II. Para realizar el pago referido en el Parágrafo anterior, el Directorio aprobara el Reglamento respectivo y el Estudio Técnico Financiero en un plazo no mayor a sesenta (60) días calendario, a partir de la publicación del presente Decreto Supremo”</b> y conforme a la aprobación por el Honorable Directorio de la MUSERPOL, del Estudio Matemático Actuarial 2016-2020, mediante Resolución de Directorio N° 26/2017 de fecha 11 de agosto de 2017 y Reglamentación de Fondo de Retiro Policial Solidario con Resolución de Directorio N° 31/2017 de 24 de agosto de 2017, modificado mediante Resolución de Directorio Nº 36/2017 de 20 de septiembre de 2017, adicionando la DISPOSICIÓN TRANSITORIA SEGUNDA (incluida mediante Resolución de Directorio Nº 36/2017 de 20 de septiembre de 2017), refiere: “Corresponderá la devolución de aportes realizados con prima de 1.85% durante la permanencia en la reserva activa, más el 5% anual de rendimiento, toda vez que estos aportes no forman parte de los parámetros de calificación establecido en el Estudio Matemático Actuarial 2016 – 2020 considerado por el Decreto Supremo Nº 3231 de 28 de junio de 2017” y  mediante nota con cite: DIRECTORIO/MUSERPOL/247/2017 de fecha 31 de octubre de 2017 concluye: “…sin embargo, para no dejar a interpretaciones el Directorio recomienda a la Dirección General Ejecutiva aplicar para el cálculo de los rendimientos a los aportes no calificados en el Beneficio de Fondo de Retiro la opción uno (1) de su propuesta, que señala: <b>la fecha del último aporte efectivizado en el periodo de la disponibilidad.”</b> y modificado mediante Resolución de Directorio Nº 51/2017 de 29 de diciembre de 2017 en la que se incorporan dos artículos referidos a la <b>“EXCEPCIÓN EN EL TRÁMITE DE FONDO DE RETIRO POR ENFERMEDADES TERMINALES” y a “FONDO DE RETIRO POR REINCORPORACIÓN”,</b> además de una disposición transitoria referida al pago de cuotas parte de trámites que cuenten con Resolución de la Comisión de Beneficios a partir de la puesta en vigencia de la MUSERPOL hasta la emisión de la Resolución de Directorio N° 50/2015.";
        $past_footer = "En cumplimiento a la normativa Técnica – Legal vigente y aprobada por el Honorable Directorio de la MUSERPOL, se procedió a realizar el procesamiento del trámite para la otorgación del beneficio de Fondo de Retiro Policial Solidario al solicitante.";

        $process = "Conforme a Dictamen Legal de la Unidad de Fondo de Retiro Policial, Cuota y Auxilio Mortuorio de la Dirección de Beneficios Económicos, mediante <b>Cite: DBE/UFRPSCAM/AL-DL N°574/2018 de fecha 28 de junio de 2018</b> y resultado del procesamiento según normativa Técnica – Legal, en cumplimiento al punto 8 del artículo 45 Procesamiento del Reglamento del beneficio de Fondo de Retiro Policial Solidario. 
        <br><br>
        Por tanto, el expediente que cursa en esta Jefatura, cuenta con los actuados requeridos.
        <br><br>
        Conforme normativa, el trámite N°454/2018 de la Regional La Paz ingresado por la Ventanilla de Atención al Afiliado de la Unidad de Otorgación del Fondo de Retiro Policial, Cuota y Auxilio Mortuorio, verificados los requisitos mediante solicitud N° 454/2018 por Ventanilla, adjuntado documentación según lo señalado en el Art. 39 del Reglamento de Fondo de Retiro Policial Solidario de la gestión 2017 y conforme al Art. 45, se detalla la documentación como resultado de la aplicación de la base técnica-legal del Estudio Matemático Actuarial 2016-2020 y Reglamento de la gestión 2017, generada y adjuntada al expediente por los funcionarios de la Unidad de Otorgación del Fondo de Retiro Policial, Cuota y Auxilio Mortuorio, según correspondan las funciones, detallando lo siguiente: ";


        $body = "";        

        ///---FILE---///
        $body_file = "";    
        $file_id = 20;
        $file = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$file_id)->first();        
        $body_file .= "Que, mediante Certificación ". $file->code .", de fecha ". Util::getStringDate($file->date) .", de Archivo de Beneficios Económicos, se establece que el trámite signado con el N° ". $retirement_fund->code." ";
        $folders=AffiliateFolder::where('affiliate_id',$affiliate->id)->get();
        if($folders->count()>0)
            $body_file .= "si tiene expediente del referido titular y cuenta con anticipo de Fondo de Retiro Policial.";
        else 
            $body_file .= "no tiene expediente del referido titular.";            
        ///---ENDIFLE--////

        /////----FINANCE----///        
        $discount = $retirement_fund->discount_types();
        $finance = $discount->where('discount_type_id','1')->first();
        $body_finance = "";
        $body_finance = "Que, mediante nota de respuesta de la Dirección Administrativa Financiera con Cite: ".$finance->pivot->code ." de fecha ". Util::getStringDate($finance->pivot->date).",";
        if(isset($finance->id)){
            $body_finance .= "se evidencia anticipo por concepto de Fondo de Retiro Policial en el monto de ".Util::formatMoney($finance->pivot->amount)." (".Util::convertir($finance->pivot->amount).").";
        }
        else{
            $body_finance .= "no se evidencia pagos o anticipos por concepto de Fondo de Retiro Policial.";
        }                         
        /////----END FINANCE---////

        ////-----LEGAL REVIEW ----////      
        $body_legal_review   = "";
        $legal_review_id = 21;
        $legal_review = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$file_id)->first();
        $body_legal_review .= "Que, mediante Certificación N° ".$legal_review->code." del Área Legal de la Unidad de Otorgación del Fondo de Retiro Policial Solidario, Cuota y Auxilio Mortuorio, de fecha ".Util::getStringDate($legal_review->date).", fue verificada y validada la documentación presentada por el titular el trámite signado con el N° ".$retirement_fund->code.".";
        /////-----END LEGAL REVIEW----///
        
        ///------ INDIVIDUAL ACCCOUTNS ------////    
        $body_accounts = "";
        $accounts_id = 22;
        $accounts = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$file_id)->first();
        $availability_code = 10;
        $availability_number_contributions = Contribution::where('affiliate_id',$affiliate->id)->where('contribution_type_id',$availability_code)->count();
        $body_accounts = "Que, mediante Certificación de Aportes N° ".$accounts->code." de Cuentas Individuales de la Unidad de Otorgación del Fondo de Retiro Policial Solidario, Cuota y Auxilio Mortuorio, de fecha ". Util::getStringDate($accounts->date) .", se verificó los últimos "."60"." aportes antes de su destino a disponibilidad de las letras (reserva activa) del titular. Mediante Certificación de Aportes en Disponibilidad N° ".$accounts->code." de Cuentas Individuales de la Unidad de Otorgación del Fondo de Retiro Policial Solidario, Cuota y Auxilio Mortuorio, de fecha ". Util::getStringDate($accounts->date) .", durante la permanencia en la reserva activa se verificó ". $availability_number_contributions ." aportes en disponibilidad.";

        ////------- INDIVIDUAL ACCOUTNS ------////

        //----- QUALIFICATION -----////      
        $body_qualification = "";
        $qualification_id = 23;
        $qualification = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$qualification_id)->first();
        $months  = $affiliate->getTotalQuotes();        
        $body_qualification .= "Que, mediante Calificación Fondo de Retiro Policial Solidario N° ".$qualification->code." de la Encargada de Calificación de la Unidad de Otorgación del Fondo de Retiro Policial Solidario, Cuota y Auxilio Mortuorio, de fecha ". Util::getStringDate($qualification->date) .", se realizó el cálculo por el periodo de ". (int)($months/12) ." y ". ($months%12) .", determinando el beneficio de Fondo de Retiro Policial Solidario por Jubilación de ". Util::formatMoney($retirement_fund->total_ret_fun) ." (". Util::convertir($retirement_fund->total_ret_fun) .")".Util::getDiscountCombinations($retirement_fund->id);
        ///----- END QUALIFICATION ----////
        

        ////----- DUE -----////
        
        $discounts = $retirement_fund->discount_types();
        $discount = $discounts->where('discount_type_id','3')->first();
        $loans = InfoLoan::where('affiliate_id',$affiliate->id)->get();
        $body_due = "Que, mediante nota ".$discount->pivot->note. "de la fecha ".Util::getStringDate($discount->pivot->date). "la Dirección de Estrategias Sociales e Inversiones, certificaque el titular no cuenta con deuda en curso de pago a MUSERPOL y por concepto de garantes adeuda a los señores ";        
        $num_loans = $loans->count();        
        $i=0;
        foreach($loans as $loan){
            $i++;
            if($i!=1)
            {
                if($num_loans-$i==0)
                    $body_due .= " y ";
                else
                    $body_due .= ", ";
            }
            $body_due.= $loan->affiliate_guarantor->fullName()." con C.I. N° ".$loan->affiliate_guarantor->identity_card." en la suma de Bs ".Util::formatMoney($loan->amount)." (".Util::convertir($discount->pivot->amount);
        }
        $body_due .= " en conformidad al contrato de préstamo Nro. ".$discount->pivot->code.".";         
        

        $conclusion   = "";
        $headship_review_id = 25;
        $headship_review = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$file_id)->first();

        $conclusion = "Se realizó la revisión de la documentación citada anteriormente, verificando su correcta emisión y contenido, en base al Dictamen Legal con Cite: ".$headship_review->code." de fecha ".Util::getStringDate($headship_review->date)." se establece que";
        if($affiliate->gender == 'F'){
            $conclusion .= " la Señora ";
        }
        else {
            $conclusion .= " el Señor ";
        }
        $conclusion .= $affiliate->fullNameWithDegree ()." con C.I. N° ".$affiliate->identity_card." ".$affiliate->city_identity_card->first_shortened.". cumple con los requisitos de acuerdo a Reglamento y se le reconocen los derechos para otorgar el beneficio de Fondo de Retiro Policial Solidario por ".$retirement_fund->procedure_modality->name." por el periodo de ". (int)($months/12) ." AÑOS y ". ($months%12) ." MESES, determinando el monto de ".Util::formatMoney($retirement_fund->ret_fun_total)." (".Util::convertir($retirement_fund->ret_fun_total)."), descontando la deuda por concepto de garantes de Bs14.327,85 (CATORCE MIL TRESCIENTOS VEINTE SIETE 85/100 BOLIVIANOS), a solicitud de la Dirección de Estrategias Sociales e Inversiones, reconocer el  Fondo de Retiro Policial Solidario por Bs27.811,63 (VEINTISIETE MIL OCHOCIENTOS ONCE 63/100 BOLIVIANOS),  a favor de los derechohabientes según el siguiente detalle:";

        $beneficiaries = RetFunBeneficiary::where('retirement_fund_id',$retirement_fund->id)->get();

        $payments = [];
        $conclusion .= "Según información contenida en el Certificado de Descendencia presentado por la solicitante, mantener en reserva la cuota parte de: ";
        foreach($beneficiaries as $beneficiary){
            $legal_guardian  = RetFunLegalGuardianbeneficiary::where('ret_fun_beneficiary_id',$beneficiary->id)->first();
            $payment = "";
            if(!isset($legal_guardian)){
                if($beneficiary->geneder == "F")
                {
                    $payment .= "Sra. ";
                }
                else{
                    $payment .= "Sr. ";
                }
            }
            else{
                $payment .= "Menor ";
            }
            if($beneficiary->state)
            $payment .= "Según información contenida en el Certificado de Descendencia presentado por la solicitante, mantener en reserva la cuota parte de: ";
            $payment .= Util::fullName($beneficiary). " con CI. N° ".$beneficiary->identity_card." ".$beneficiary->city_identity_card->first_shortened."., en el monto de Bs ".Util::formatMoney($beneficiary->amount_total)." (".Util::convertir($beneficiary->amount_total)."), en calidad de ".$beneficiary->kinship->name."." ;            

            array_push($payments,$payment);
        }
        $end_conclusion = "Elevo el presente informe a su persona para su conocimiento y consideración.";
        $from = 'Lic. '.Util::fullName(Auth::user()).'
        <br><b>JEFE DE UNIDAD DE OTORGACIÓN DE FONDO DE RETIRO POLICIAL, CUOTA Y AUXILIO MORTUORIO “MUSERPOL”</b>';
        $to = 'Lic. DAEN. Gabriela J. Bustillos Landaeta<br><b>
        DIRECTORA DE BENEFICIOS ECONÓMICOS “MUSERPOL"</b>';
        //return $conclusion;

            $number = Util::getNextAreaCode($retirement_fund->id);
            $data = [
                'ret_fun' => $retirement_fund,
                //'beneficiaries'    =>  $beneficiaries,
                'correlative'  =>  $number,
                'affiliate' =>  $affiliate,
                'actual_city'  =>  Auth::user()->city->name,
                'actual_date'  =>  Util::getStringDate(date('Y-m-d')),    
                'head'    =>  $head,
                'past'   =>  $past,
                'past_footer'   =>  $past_footer,
                'process'   =>  $process,
                'body_file'  =>  $body_file,
                'body_accounts'  =>  $body_accounts,
                'body_finance'  =>  $body_finance,
                'body_legal_review'  =>  $body_legal_review,
                'body_qualification'  =>  $body_qualification,
                'body_due'  =>  $body_due,
                'conclusion'   =>  $conclusion,
                'payments'  =>  $payments,
                'end_conclusion'    =>  $end_conclusion,
                'from'  =>  $from,
                'to'    =>  $to                
            ];
            
            return \PDF::loadView('ret_fun.print.headship_review', $data)
            ->setPaper('letter')
            ->setOption('encoding', 'utf-8')
            ->stream("jefaturaRevision.pdf");              
    }
    public function printLegalResolution($ret_fun_id){

        $retirement_fund =  RetirementFund::find($ret_fun_id);
        $affiliate = Affiliate::find($retirement_fund->affiliate_id);
        
        $art = [
            3  =>  28,
            4 =>  29,
            5 =>  30,
            6 =>  30,
            7 =>  31
        ]; 

        $law = 'Que, el Decreto Supremo N° 1446 de 19 de diciembre de 2012, Artículo 2 de la CREACIÓN Y
        NATURALEZA JURÍDICA, Parágrafo I establece: “Se crea la Mutual de Servicios al Policía –
        MUSERPOL, como institución pública descentralizada, de duración indefinida y patrimonio
        propio, con autonomía de gestión administrativa, financiera, legal y técnica, bajo tuición del
        Ministerio de Gobierno.” El Artículo 5 del ÁMBITO DE APLICACIÓN, Parágrafos I y II, refiere: “I.
        El presente Decreto Supremo es aplicable a todas y todos los afiliados activos y pasivos de la
        Policía Boliviana, así como a sus beneficiarios de acuerdo a reglamento. II. Para los afiliados
        activos y pasivos de la Policía Boliviana que hayan sido dados de baja, de forma voluntaria o
        forzosa, los beneficios establecidos en el presente Decreto Supremo estarán sujetos a
        reglamentación interna”.
        Que, el Decreto Supremo N° 2829, de 06 de julio de 2016, modificatorio al Decreto Supremo Nº
        1446 de 19 de diciembre de 2012, en el Artículo 2 de las MODIFICACIONES, Parágrafo III
        señala: “Se modifica el Parágrafo II del Artículo 14 del Decreto supremo Nº 1446, de 19 de
        diciembre de 2012, con el siguiente texto: “II. El aporte y pago de los beneficios establecidos en
        los incisos a) (Fondo de Retiro) y b) del Parágrafo precedente, serán objeto de un estudio
        técnico financiero y estudio actuarial que asegure su sostenibilidad, en el marco del principio de
        solidaridad”.
        Que, el Decreto Supremo N°3231, de 28 de junio de 2017, modificatoria al Decreto Supremo Nº
        1446 de 19 de diciembre de 2012, en el Artículo 2 de las MODIFICACIONES, Parágrafos I, III,
        IV, V y VI señala: “I. Se modifica el inciso c) del Artículo 3 del Decreto Supremo N°1446, de 19
        de diciembre de 2012, con el siguiente texto: “c) Otorgar el beneficio variable del Fondo de
        Retiro Policial Solidario, en el marco del principio de Solidaridad;” III. Se modifica el inciso a) del
        Parágrafo I del Artículo 12 del Decreto Supremo N°1446, de 19 de diciembre de 2012, con el
        siguiente texto: “a) Los aportes de los afiliados del sector activo de la Policía Boliviana
        transferidos por el Comando General de acuerdo a estudio actuarial aprobado; IV. Se modifica
        el inciso a) del Parágrafo I del Artículo 14 del Decreto Supremo N°1446, de 19 de diciembre de
        2012, con el siguiente texto: “a) Fondo de Retiro Policial Solidario”; “V. Se modifica el Parágrafo
        III del Artículo 14 del Decreto Supremo N°1446, de 19 de diciembre de 2012, con el siguiente
        texto: “III. Los beneficios señalados en el presente Artículo se rigen por los principios de
        equidad y solidaridad, debiendo ser otorgados a todos los afiliados, aportantes de la Policía
        Boliviana en sus diferentes sectores y niveles sin ninguna distinción”; “VI. Se modifica el Artículo
        15 del Decreto Supremo N°1446, de 19 de diciembre de 2012, con el siguiente texto:
        ARTICULO 15 (FONDO DE RETIRO POLICIAL SOLIDARIO). Es el beneficio que brinda
        protección a los miembros del servicio activo y sus derechohabientes, mediante el
        reconocimiento de un pago único, con motivo y oportunidad del retiro definitivo de la actividad
        remunerada dependiente de la Policía Boliviana, el cual será administrado por la MUSERPOL; a
        ser otorgado en el marco del principio de solidaridad, cuando el retiro se produzca por: a)
        Jubilación, b) Fallecimiento del titular, c) Retiro forzoso, d) Retiro voluntario”.
        Que, el Estudio Matemático Actuarial 2016 – 2020, aprobado mediante Resolución de Directorio
        Nº 26/2017, de 11 de agosto de 2017, determina la modalidad y parámetros de calificación para
        la otorgación del beneficio de Fondo de Retiro Policial Solidario.
        <br><br>
        Que, el Reglamento de Fondo de Retiro Policial Solidario, aprobado mediante Resolución de
        Directorio Nº 31/2017 de 24 de agosto de 2017 y modificado mediante Resolución de Directorio
        Nº 36/2017 de 20 de septiembre de 2017, Artículos 2,3,5,7,8,10,12,13,15,26,27,'.$art[$retirement_fund->procedure_modality_id].',37,39,41,44
        y 45, reconocen el derecho de la otorgación del pago de Fondo de Retiro Policial Solidario.
        <br><br>
        Que, el Reglamento de Fondo de Retiro Policial Solidario, Artículo 15 del RECONOCIMIENTO
        DE LOS APORTES, señala: “La MUSERPOL reconoce la densidad de aportes efectuados a
        partir de mayo de 1976, al Ex Fondo Complementario de Seguridad Social de la Policía
        Nacional y a la extinta Mutual de Seguros del Policía MUSEPOL”.
        <br><br>
        Que, el Reglamento de Fondo de Retiro Policial Solidario, Artículo 20 de la PROCEDENCIA del
        pago global, Parágrafo I señala: “El pago global de aportes procederá, cuando el afiliado no
        haya cumplido con 60 cotizaciones (5 años) para acceder al pago del Fondo de Retiro Policial
        Solidario, antes de su desvinculación laboral con la Policía Boliviana, siendo las causales
        reconocidas para acceder a este pago el fallecimiento o retiro forzoso por invalidez
        permanente.”.
        <br><br>
        Que, el Reglamento de Fondo de Retiro Policial Solidario, Artículo 45 del PROCESAMIENTO,
        punto 6 refiere: “Con la liquidación, el trámite será remitido a Jefatura para la verificación de
        actuados y puesta en conocimiento a la Dirección de Estrategias Sociales e Inversiones”
        Artículo 73 de la Retención por Garantes, refiere: “Para dar curso a la solicitud de recuperación
        de deuda efectuada por la Dirección de Estrategias Sociales e Inversiones, ésta deberá contar
        con respaldo documental que el titular tiene conocimiento que se efectuará un descuento a
        favor de su garante con cargo a su beneficio de Fondo de Retiro Policial”.
        <br><br>
        Que, el Reglamento de Fondo de Retiro Policial Solidario, Artículo 52 de los Anticipos de Fondo
        de Retiro, Parágrafo II, refiere: “II El saldo pendiente de pago por anticipo, que hubiese sido
        solicitado antes de la disolución de la Ex MUSEPOL, será calificado y cancelado de acuerdo a
        los parámetros establecidos en la Reglamentación vigente a esa fecha”. “III. El saldo pendiente
        de pago por anticipo, que hubiese sido solicitado posterior a la disolución de la Ex MUSEPOL,
        será calificado y cancelado de acuerdo a los parámetros establecidos en el Estudio Matemático
        Actuarial 2016 – 2020 y el presente Reglamento”.
        <br><br>
        Que, el Reglamento de Fondo de Retiro Policial Solidario, aprobado mediante Resolución de
        Directorio Nº 31/2017 de 24 de agosto de 2017 y modificado mediante Resolución de Directorio
        Nº 36/2017 de 20 de septiembre de 2017, en su Artículo 55 de la DEFINICIÓN Y
        CONFORMACIÓN, Parágrafo I refiere: “I. La Comisión de Beneficios Económicos es la
        instancia técnica legal que realiza el procedimiento administrativo para la otorgación del
        beneficio de Fondo de Retiro Policial Solidario”. Por consiguiente, la Resolución Administrativa
        Nº 031/2017 de 04 de diciembre de 2017, conforma la Comisión de Beneficios Económicos, en
        cumplimiento al Reglamento.
        <br><br>
        Que, el Reglamento de Fondo de Retiro Policial Solidario, aprobado mediante Resolución de
        Directorio Nº 31/2017 de 24 de agosto de 2017 y modificado mediante Resolución de Directorio
        Nº 36/2017 de 20 de septiembre de 2017, en la DISPOSICIÓN TRANSITORIA SEGUNDA
        (Incluida mediante Resolución de Directorio Nº 36/2017 de 20 de septiembre de 2017), refiere:
        “Corresponderá la devolución de aportes realizados con prima de 1.85% durante la
        permanencia en la reserva activa, más el 5% anual de rendimiento, toda vez que estos aportes
        no forman parte de los parámetros de calificación establecido en el Estudio Matemático
        Actuarial 2016 – 2020 considerado por el Decreto Supremo Nº 3231 de 28 de junio de 2017”.';

        $due = 'Que, mediante Resolución de la Comisión de Prestaciones Nº de fecha , se otorgó en calidad
        de ANTICIPO del 50% el monto de Bs() a favor del Sr. SOF. 1ro. MARIO BAUTISTA
        MANCILLA con C.I. 2215955 LP .';

        $discount = $retirement_fund->discount_types();
        $finance = $discount->where('discount_type_id','1')->first();
        $body_finance = "";
        $body_finance = "Que, mediante nota de respuesta de la Dirección Administrativa Financiera con Cite: ".$finance->pivot->code ." de fecha ". Util::getStringDate($finance->pivot->date).",";
        if(isset($finance->id)){
            $body_finance .= "se evidencia anticipo por concepto de Fondo de Retiro Policial en el monto de ".Util::formatMoney($finance->pivot->amount)." (".Util::convertir($finance->pivot->amount).").";
        }
        else{
            $body_finance .= "no se evidencia pagos o anticipos por concepto de Fondo de Retiro Policial.";
        } 
        $reception = 'Que, en fecha '.Util::getStringDate($retirement_fund->reception_date).', el '.$affiliate->fullNameWithDegree().' con C.I.
        '.$affiliate->identity_card.' '.$affiliate->city_identity_card->first_shortened.' , solicita el pago de Fondo de Retiro Policial, adjuntando documentación solicitada
        por la Unidad; por consiguiente, habiéndose cumplido con los requisitos de orden establecido
        en el Reglamento de Fondo de Retiro Policial Solidario, se dio curso con el trámite.';

        //----- QUALIFICATION -----////      
        $body_qualification = "";
        $qualification_id = 23;
        $qualification = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$qualification_id)->first();
        $months  = $affiliate->getTotalQuotes();        
        $body_qualification .= "Que, mediante Calificación Fondo de Retiro Policial Solidario N° ".$qualification->code." de la Encargada de Calificación de la Unidad de Otorgación del Fondo de Retiro Policial Solidario, Cuota y Auxilio Mortuorio, de fecha ". Util::getStringDate($qualification->date) .", se realizó el cálculo por el periodo de ". (int)($months/12) ." y ". ($months%12) .", determinando el beneficio de Fondo de Retiro Policial Solidario por Jubilación de ". Util::formatMoney($retirement_fund->total_ret_fun) ." (". Util::convertir($retirement_fund->total_ret_fun) .")".Util::getDiscountCombinations($retirement_fund->id);
        ///----- END QUALIFICATION ----////

        $legal_dictum_id = 24;
        $legal_dictum = RetFunCorrelative::where('retirement_fund_id',$retirement_fund->id)->where('wf_state_id',$qualification_id)->first();
        $body_legal_dictum = 'Que, habiéndose verificado el procesamiento establecido en el Reglamento de Fondo de Retiro
        Policial Solidario, se procedió con la emisión de DICTAMEN LEGAL '.$legal_dictum->code.' de '.Util::getStringDate($legal_dictum->date).' , para la otorgación del beneficio de Fondo de Retiro Policial Solidario por
        '.$retirement_fund->procedure_modality->name.'.';

        $then = 'La Comisión de Beneficios Económicos de la Mutual de Servicios al Policía “MUSERPOL” en
        uso de sus facultades y en observancia al Reglamento de Fondo de Retiro Policial Solidario:';
        $number = Util::getNextAreaCode($retirement_fund->id);
        
        $data = [
            'retirement_fund'   =>  $retirement_fund,
            'law'  =>  $law,
            'correlative'   =>  $number,
            'ret_fun' => $retirement_fund,                        
            'affiliate' =>  $affiliate,
            'actual_city'  =>  Auth::user()->city->name,
            'actual_date'  =>  Util::getStringDate(date('Y-m-d')), 
        ];
        return \PDF::loadView('ret_fun.print.legal_resolution', $data)
            ->setPaper('letter')
            ->setOption('encoding', 'utf-8')
            ->stream("jefaturaRevision.pdf"); 
    }
    private function getFlagy($num,$pos){
        if($num == ($pos+1))
            return ", ";
        if($num == ($pos+2))
            return " y la suma de ";
        return ;
    }

}
