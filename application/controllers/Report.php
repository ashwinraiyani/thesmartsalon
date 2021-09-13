<?php
class Report extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        checkSession();
    }

    public function General()
    {

        if (!check_role_assigned('generalreport', 'view')) {
            $this->session->set_flashdata('error_msg', NOT_ALLOW_ACCESS);
            redirect(base_url('Dashboard'));
        }

        $searchtxt = array();
        $end = "";
        $start = "";

        $search = $this->input->post();
        if (isset($search["clearall"])) {
            $session_data = array(
                'start' => '',
                'end' => ''
            );
            $searchtxt['start'] = "";
            $searchtxt['end'] = "";
            // Add user data in session
            $this->session->set_userdata('generalreport', $session_data);
        } else if (isset($search["submit"])) {
            if (isset($search["start"])) {
                if ($search["start"] != null) {
                    $start = StringRepair($search["start"]);
                    $searchtxt['start'] = $start;
                }
            }
            if (isset($search["end"])) {
                if ($search["end"] != null) {
                    $end = StringRepair($search["end"]);
                    $searchtxt['end'] = $end;
                }
            }
        } else {
            if (!empty($this->session->userdata['generalreport']['start'])) {
                $start = StringRepair($this->session->userdata['generalreport']['start']);
                $searchtxt['start'] = $start;
            }
            if (!empty($this->session->userdata['generalreport']['end'])) {
                $end = StringRepair($this->session->userdata['generalreport']['end']);
                $searchtxt['end'] = $end;
            }
        }

        if ($searchtxt["start"] == "" && $searchtxt["end"] == "") {
            $searchtxt["start"] = date("Y-m-d");
            $searchtxt["end"] = date("Y-m-d");
        }

        $params['total_customers'] = 0;
        $params['total_new_customers'] = 0;
        $params['total_sales'] = 0;
        $params['total_discount'] = 0;
        $params['total_cash'] = 0;
        $params['total_crdb'] = 0;
        $params['total_wallet'] = 0;
        $params['service_sales'] = 0;
        $params['product_sales'] = 0;

        $msql = "";
        $cid = $this->session->userdata['logged_in']['companyid'];
        $managerid = $this->session->userdata['logged_in']['managerid'];
        if ($managerid > 0) {
            $msql = ' and managerid=' . $managerid;
        }

        $query = "select coalesce(count(id),0) as total_customers from " . TBL_INVOICE_MASTER . " where isdelete=0 and  billdate >= '" . $searchtxt['start'] . "' and billdate <= '" . $searchtxt['end'] . "' and cid=" . $cid . " " . $msql;
        $res = $this->Queries->getSingleRecord($query);
        if ($res != null) {
            $params['total_customers'] = $res->total_customers;
        }

        $query = "select coalesce(count(id),0) as total_customers from  " . TBL_CUSTOMER . " where isdelete=0 and firstbilldate >=  '" . $searchtxt['start'] . "' and firstbilldate <= '" . $searchtxt['end'] . "' and cid=" . $cid . " " . $msql . " ";
        $res = $this->Queries->getSingleRecord($query);
        if ($res != null) {
            $params['total_new_customers'] = $res->total_customers;
        }

        $query = "select coalesce(sum(total_amt),0) as total_sales, coalesce(sum(final_amt),0) as total_amount,coalesce(sum(discount_amt),0) as total_discount,coalesce(sum(amount_paid),0) as total_payment,coalesce(sum(wallet_amount),0) as wallet_amount,  coalesce(sum(CASE WHEN paymod = 1 THEN amount_paid END),0) as total_cash,coalesce(sum(CASE WHEN paymod = 2 THEN amount_paid END),0) as total_crdb,coalesce(sum(CASE WHEN paymod = 3 THEN amount_paid END),0) as total_wallet from " . TBL_INVOICE_MASTER . " where isdelete=0 and  billdate >= '" . $searchtxt['start'] . "' and billdate <= '" . $searchtxt['end'] . "'and cid=" . $cid . " " . $msql;

        $res = $this->Queries->getSingleRecord($query);
        if ($res != null) {
            $params['total_sales'] = $res->total_sales;
            $params['total_amount'] = $res->total_amount;
            $params['wallet_amount'] = $res->wallet_amount;
            $params['total_discount'] = $res->total_discount;
            $params['total_payment'] = $res->total_payment;
            $params['total_cash'] = $res->total_cash;
            $params['total_crdb'] = $res->total_crdb;
            $params['total_wallet'] = $res->total_wallet;
        }

        $query = "select coalesce(sum(CASE WHEN ctype = 1 THEN amount END),0) as total_service_sales, coalesce(sum(CASE WHEN ctype = 2 THEN amount END),0) as total_product_sales, coalesce(sum(CASE WHEN ctype = 3 THEN amount END),0) as total_package_sales from " . TBL_INVOICE_ITEM . " where isdelete=0 and  sdate between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and cid=" . $cid . $msql;
        $res = $this->Queries->getSingleRecord($query);
        if ($res != "") {
            $params['total_service_sales'] = $res->total_service_sales;
            $params['total_product_sales'] = $res->total_product_sales;
            $params['total_package_sales'] = $res->total_package_sales;
        }

        $query = "select coalesce(sum(actual_amt),0) as total_wallet from " . TBL_CUSTOMER_WALLET . " where isdelete=0 and  createdon between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and cid=" . $cid . $msql;
        $res = $this->Queries->getSingleRecord($query);
        if ($res != "") {
            $params['total_soft_wallet'] = $res->total_wallet;
        }

        $query = "select coalesce(sum(amount_paid),0) as total_expense from " . TBL_EXPENSE . " where isdelete=0 and  exp_date between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and cid=" . $cid . $msql;
        $res = $this->Queries->getSingleRecord($query);
        if ($res != "") {
            $params['total_expense'] = $res->total_expense;
        }
        $query = "select coalesce(sum(amount_paid),0) as total_purchase from " . TBL_PURCHASE_MASTER . " where isdelete=0 and  billdate between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and cid=" . $cid . $msql;
        $res = $this->Queries->getSingleRecord($query);
        if ($res != "") {
            $params['total_purchase'] = $res->total_purchase;
        }

        $query = "select id,user_name from " . TBL_USERINFO . " where isdelete=0 and isbarber=1 and cid=" . $cid . $msql . " order by id";
        $params['userlist'] = $this->Queries->get_tab_list($query, 'id', 'user_name');

        $session_data = array(
            'start' => $searchtxt["start"],
            'end' => $searchtxt["end"],
            'userid' => $searchtxt["userid"],
        );
        // Add user data in session
        $this->session->set_userdata('generalreport', $session_data);

        $this->load->view('Report/General', $params);
    }

    public function Bill()
    {
        if (!check_role_assigned('billreport', 'view')) {
            $this->session->set_flashdata('error_msg', NOT_ALLOW_ACCESS);
            redirect(base_url('Dashboard'));
        }

        $searchtxt = array();


        $end = "";
        $start = "";
        $custid = "";
        $search = $this->input->post();

        if (isset($search["clearall"])) {
            $session_data = array(
                'start' => '',
                'end' => '',
                'custid' => ''
            );
            $searchtxt['start'] = "";
            $searchtxt['end'] = "";
            $searchtxt['custid'] = "";
            // Add user data in session
            $this->session->set_userdata('billreport', $session_data);
        } else if (isset($search["submit"])) {
            if (isset($search["start"])) {
                if ($search["start"] != null) {
                    $start = StringRepair($search["start"]);
                    $searchtxt['start'] = $start;
                }
            }
            if (isset($search["end"])) {
                if ($search["end"] != null) {
                    $end = StringRepair($search["end"]);
                    $searchtxt['end'] = $end;
                }
            }
            if (isset($search["customer"])) {
                if ($search["customer"] != null) {
                    $custid = StringRepair($search["customer"]);
                    $searchtxt['custid'] = $custid;
                }
            }
        } else {
            if (!empty($this->session->userdata['billreport']['start'])) {
                $start = StringRepair($this->session->userdata['billreport']['start']);
                $searchtxt['start'] = $start;
            }
            if (!empty($this->session->userdata['billreport']['end'])) {
                $end = StringRepair($this->session->userdata['billreport']['end']);
                $searchtxt['end'] = $end;
            }
            if (!empty($this->session->userdata['billreport']['custid'])) {
                $custid = StringRepair($this->session->userdata['billreport']['custid']);
                $searchtxt['custid'] = $custid;
            }
        }

        if ($searchtxt["start"] == "" && $searchtxt["end"] == "") {
            $searchtxt["start"] = date("Y-m-d");
            $searchtxt["end"] = date("Y-m-d");
        }
        $sql = "";
        if ($custid != "" and is_numeric($custid)) {
            $sql = ' and t1.custid=' . $custid;
        }
        $msql = "";
        $cid = $this->session->userdata['logged_in']['companyid'];
        $managerid = $this->session->userdata['logged_in']['managerid'];
        if ($managerid > 0) {
            $msql = ' and t1.managerid=' . $managerid;
            $msql2 = ' and managerid=' . $managerid;
        }


        $query = "select t1.billno,t1.billdate,t1.total_amt,t1.discount_amt,t1.final_amt,t1.amount_paid,t2.fname,t2.lname,t3.coupon_code from " . TBL_INVOICE_MASTER . " as t1 left join " . TBL_CUSTOMER . " as t2 on t2.id = t1.custid left join " . TBL_COUPON . " as t3 on t3.id = t1.couponid where t1.isdelete=0 $sql and  t1.billdate between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and t2.cid=" . $cid . " " . $msql;
        $params['invoicelist'] = $this->Queries->getRecord($query);


        $query = "select id,concat( fname,' ',lname)as customer_name from " . TBL_CUSTOMER . " where isdelete=0 and cid=" . $cid . " " . $msql2 . " order by id";
        $params['customerlist'] = $this->Queries->get_tab_list($query, 'id', 'customer_name');

        $session_data = array(
            'start' => $searchtxt["start"],
            'end' => $searchtxt["end"],
            'custid' => $searchtxt["custid"],
        );
        // Add user data in session
        $this->session->set_userdata('billreport', $session_data);

        $this->load->view('Report/Bill', $params);
    }
    public function Purchase()
    {

        if (!check_role_assigned('purchasereport', 'view')) {
            $this->session->set_flashdata('error_msg', NOT_ALLOW_ACCESS);
            redirect(base_url('Dashboard'));
        }
        $searchtxt = array();

        $end = "";
        $start = "";
        $vendorid = "";
        $search = $this->input->post();

        if (isset($search["clearall"])) {
            $session_data = array(
                'start' => '',
                'end' => '',
                'vendorid' => ''
            );
            $searchtxt['start'] = "";
            $searchtxt['end'] = "";
            $searchtxt['vendorid'] = "";
            // Add user data in session
            $this->session->set_userdata('purchasereport', $session_data);
        } else if (isset($search["submit"])) {
            if (isset($search["start"])) {
                if ($search["start"] != null) {
                    $start = StringRepair($search["start"]);
                    $searchtxt['start'] = $start;
                }
            }
            if (isset($search["end"])) {
                if ($search["end"] != null) {
                    $end = StringRepair($search["end"]);
                    $searchtxt['end'] = $end;
                }
            }
            if (isset($search["vendor"])) {
                if ($search["vendor"] != null) {
                    $vendorid = StringRepair($search["vendor"]);
                    $searchtxt['vendorid'] = $vendorid;
                }
            }
        } else {
            if (!empty($this->session->userdata['purchasereport']['start'])) {
                $start = StringRepair($this->session->userdata['purchasereport']['start']);
                $searchtxt['start'] = $start;
            }
            if (!empty($this->session->userdata['purchasereport']['end'])) {
                $end = StringRepair($this->session->userdata['purchasereport']['end']);
                $searchtxt['end'] = $end;
            }
            if (!empty($this->session->userdata['purchasereport']['vendorid'])) {
                $vendorid = StringRepair($this->session->userdata['purchasereport']['vendorid']);
                $searchtxt['vendorid'] = $vendorid;
            }
        }

        if ($searchtxt["start"] == "" && $searchtxt["end"] == "") {
            $searchtxt["start"] = date("Y-m-d");
            $searchtxt["end"] = date("Y-m-d");
        }
        $sql = "";
        if ($vendorid != "" and is_numeric($vendorid)) {
            $sql = ' and t1.vendorid=' . $vendorid;
        }
        $msql = "";
        $cid = $this->session->userdata['logged_in']['companyid'];
        $managerid = $this->session->userdata['logged_in']['managerid'];
        if ($managerid > 0) {
            $msql = ' and t1.managerid=' . $managerid;
        }

        $query = "select t1.billno,t1.billdate,t1.total_amt,t1.discount_amt,t1.tax_amt,t1.shipping_charges,t1.final_amt,t1.amount_paid,t2.vendor_name,t3.paymod from " . TBL_PURCHASE_MASTER . " as t1 left join " . TBL_VENDOR . " as t2 on t2.id = t1.vendorid left join " . TBL_PAYMOD . " as t3 on t3.id = t1.paymod where t1.isdelete=0 $sql and  t1.billdate between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and t1.cid=" . $cid . " " . $msql;
        $params['purchaselist'] = $this->Queries->getRecord($query);


        $query = "select id,vendor_name from " . TBL_VENDOR . " where isdelete=0 and cid=" . $cid . " order by id";
        $params['vendorlist'] = $this->Queries->get_tab_list($query, 'id', 'vendor_name');

        $session_data = array(
            'start' => $searchtxt["start"],
            'end' => $searchtxt["end"],
            'vendorid' => $searchtxt["vendorid"],
        );
        // Add user data in session
        $this->session->set_userdata('purchasereport', $session_data);
        $params["managerhide"] = 1;
        $this->load->view('Report/Purchase', $params);
    }
    public function Treatment()
    {
        if (!check_role_assigned('treatmentreport', 'view')) {
            $this->session->set_flashdata('error_msg', NOT_ALLOW_ACCESS);
            redirect(base_url('Dashboard'));
        }

        $searchtxt = array();

        $end = "";
        $start = "";
        $staffid = "";
        $source = "";
        $search = $this->input->post();

        if (isset($search["clearall"])) {
            $session_data = array(
                'start' => '',
                'end' => '',
                'staffid' => '',
                'source' => ''
            );
            $searchtxt['start'] = "";
            $searchtxt['end'] = "";
            $searchtxt['staffid'] = "";
            $searchtxt['source'] = "";
            // Add user data in session
            $this->session->set_userdata('treatmentreport', $session_data);
        } else if (isset($search["submit"])) {
            if (isset($search["start"])) {
                if ($search["start"] != null) {
                    $start = StringRepair($search["start"]);
                    $searchtxt['start'] = $start;
                }
            }
            if (isset($search["end"])) {
                if ($search["end"] != null) {
                    $end = StringRepair($search["end"]);
                    $searchtxt['end'] = $end;
                }
            }
            if (isset($search["staff"])) {
                if ($search["staff"] != null) {
                    $staffid = StringRepair($search["staff"]);
                    $searchtxt['staffid'] = $staffid;
                }
            }
            if (isset($search["treatment"])) {
                if ($search["treatment"] != null) {
                    $source = StringRepair($search["treatment"]);
                    $searchtxt['source'] = $source;
                }
            }
        } else {
            if (!empty($this->session->userdata['treatmentreport']['start'])) {
                $start = StringRepair($this->session->userdata['treatmentreport']['start']);
                $searchtxt['start'] = $start;
            }
            if (!empty($this->session->userdata['treatmentreport']['end'])) {
                $end = StringRepair($this->session->userdata['treatmentreport']['end']);
                $searchtxt['end'] = $end;
            }
            if (!empty($this->session->userdata['treatmentreport']['staffid'])) {
                $staffid = StringRepair($this->session->userdata['treatmentreport']['staffid']);
                $searchtxt['staffid'] = $staffid;
            }
            if (!empty($this->session->userdata['treatmentreport']['source'])) {
                $source = StringRepair($this->session->userdata['treatmentreport']['source']);
                $searchtxt['source'] = $source;
            }
        }

        if ($searchtxt["start"] == "" && $searchtxt["end"] == "") {
            $searchtxt["start"] = date("Y-m-d");
            $searchtxt["end"] = date("Y-m-d");
        }
        $sql = "";
        if ($staffid != "" and is_numeric($staffid)) {
            $sql .= ' and t1.userid=' . $staffid;
        }
        if ($source != "" and is_numeric($source)) {
            $sql .= ' and t1.source=' . $source;
        }

        $msql = "";
        $cid = $this->session->userdata['logged_in']['companyid'];
        $managerid = $this->session->userdata['logged_in']['managerid'];
        if ($managerid > 0) {
            $msql = ' and t2.managerid=' . $managerid;
            $msql2 = ' and managerid=' . $managerid;
        }

        $query = "select t2.billno,t2.billdate,t1.sdate,t1.price,t1.qty,t1.amount,t1.amt_share,t3.person_name,t4.treatment from " . TBL_INVOICE_ITEM . " as t1 left join " . TBL_INVOICE_MASTER . " as t2 on t2.id = t1.invid left join " . TBL_USERINFO . " as t3 on t3.id = t1.userid left join " . TBL_TREATMENT . " as t4 on t4.id = t1.source where t1.ctype=1 and t1.isdelete=0 $sql and  t1.sdate between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and t2.cid=" . $cid . $msql;
        $params['treatmentlist'] = $this->Queries->getRecord($query);

        $query = "select id,person_name from " . TBL_USERINFO . " where isdelete=0 and user_type > 2 and cid=" . $cid . $msql2 . " order by id";
        $params['userlist'] = $this->Queries->get_tab_list($query, 'id', 'person_name');

        $query = "select id,treatment from " . TBL_TREATMENT . " where isdelete=0 and cid=" . $cid . $msql2 . " order by id ";
        $params['treatlist'] = $this->Queries->get_tab_list($query, 'id', 'treatment');

        $session_data = array(
            'start' => $searchtxt["start"],
            'end' => $searchtxt["end"],
            'staffid' => $searchtxt["staffid"],
            'source' => $searchtxt["source"]
        );
        // Add user data in session
        $this->session->set_userdata('treatmentreport', $session_data);

        $this->load->view('Report/Treatment', $params);
    }
    public function Product()
    {
        if (!check_role_assigned('productreport', 'view')) {
            $this->session->set_flashdata('error_msg', NOT_ALLOW_ACCESS);
            redirect(base_url('Dashboard'));
        }
        $searchtxt = array();

        $end = "";
        $start = "";
        $staffid = "";
        $source = "";
        $search = $this->input->post();

        if (isset($search["clearall"])) {
            $session_data = array(
                'start' => '',
                'end' => '',
                'staffid' => '',
                'source' => ''
            );
            $searchtxt['start'] = "";
            $searchtxt['end'] = "";
            $searchtxt['staffid'] = "";
            $searchtxt['source'] = "";
            // Add user data in session
            $this->session->set_userdata('productreport', $session_data);
        } else if (isset($search["submit"])) {
            if (isset($search["start"])) {
                if ($search["start"] != null) {
                    $start = StringRepair($search["start"]);
                    $searchtxt['start'] = $start;
                }
            }
            if (isset($search["end"])) {
                if ($search["end"] != null) {
                    $end = StringRepair($search["end"]);
                    $searchtxt['end'] = $end;
                }
            }
            if (isset($search["staff"])) {
                if ($search["staff"] != null) {
                    $staffid = StringRepair($search["staff"]);
                    $searchtxt['staffid'] = $staffid;
                }
            }
            if (isset($search["product"])) {
                if ($search["product"] != null) {
                    $source = StringRepair($search["product"]);
                    $searchtxt['source'] = $source;
                }
            }
        } else {
            if (!empty($this->session->userdata['productreport']['start'])) {
                $start = StringRepair($this->session->userdata['productreport']['start']);
                $searchtxt['start'] = $start;
            }
            if (!empty($this->session->userdata['productreport']['end'])) {
                $end = StringRepair($this->session->userdata['productreport']['end']);
                $searchtxt['end'] = $end;
            }
            if (!empty($this->session->userdata['productreport']['staffid'])) {
                $staffid = StringRepair($this->session->userdata['productreport']['staffid']);
                $searchtxt['staffid'] = $staffid;
            }
            if (!empty($this->session->userdata['productreport']['source'])) {
                $source = StringRepair($this->session->userdata['productreport']['source']);
                $searchtxt['source'] = $source;
            }
        }

        if ($searchtxt["start"] == "" && $searchtxt["end"] == "") {
            $searchtxt["start"] = date("Y-m-d");
            $searchtxt["end"] = date("Y-m-d");
        }
        $sql = "";
        if ($staffid != "" and is_numeric($staffid)) {
            $sql .= ' and t1.userid=' . $staffid;
        }
        if ($source != "" and is_numeric($source)) {
            $sql .= ' and t1.source=' . $source;
        }
        $msql = "";
        $cid = $this->session->userdata['logged_in']['companyid'];
        $managerid = $this->session->userdata['logged_in']['managerid'];
        if ($managerid > 0) {
            $msql = ' and t2.managerid=' . $managerid;
            $msql2 = ' and managerid=' . $managerid;
        }

        $query = "select t2.billno,t2.billdate,t1.sdate,t1.price,t1.qty,t1.amount,t1.amt_share,t3.person_name,t4.prodname from " . TBL_INVOICE_ITEM . " as t1 left join " . TBL_INVOICE_MASTER . " as t2 on t2.id = t1.invid left join " . TBL_USERINFO . " as t3 on t3.id = t1.userid left join " . TBL_PRODUCTS . " as t4 on t4.id = t1.source where t1.ctype=2 and t1.isdelete=0 $sql and  t1.sdate between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and t2.cid=" . $cid . $msql;
        $params['prodlist'] = $this->Queries->getRecord($query);

        $query = "select id,person_name from " . TBL_USERINFO . " where isdelete=0 and user_type > 3 and cid=" . $cid . $msql2 . " order by id";
        $params['userlist'] = $this->Queries->get_tab_list($query, 'id', 'person_name');

        $query = "select id,prodname from " . TBL_PRODUCTS . " where isdelete=0 and cid=" . $cid . " order by id";
        $params['productlist'] = $this->Queries->get_tab_list($query, 'id', 'prodname');

        $session_data = array(
            'start' => $searchtxt["start"],
            'end' => $searchtxt["end"],
            'staffid' => $searchtxt["staffid"],
            'source' => $searchtxt["source"]
        );
        // Add user data in session
        $this->session->set_userdata('productreport', $session_data);

        $this->load->view('Report/Product', $params);
    }

    public function Package()
    {
        if (!check_role_assigned('packagereport', 'view')) {
            $this->session->set_flashdata('error_msg', NOT_ALLOW_ACCESS);
            redirect(base_url('Dashboard'));
        }

        $searchtxt = array();

        $end = "";
        $start = "";
        $staffid = "";
        $source = "";
        $search = $this->input->post();

        if (isset($search["clearall"])) {
            $session_data = array(
                'start' => '',
                'end' => '',
                'staffid' => '',
                'source' => ''
            );
            $searchtxt['start'] = "";
            $searchtxt['end'] = "";
            $searchtxt['staffid'] = "";
            $searchtxt['source'] = "";
            // Add user data in session
            $this->session->set_userdata('packagereport', $session_data);
        } else if (isset($search["submit"])) {
            if (isset($search["start"])) {
                if ($search["start"] != null) {
                    $start = StringRepair($search["start"]);
                    $searchtxt['start'] = $start;
                }
            }
            if (isset($search["end"])) {
                if ($search["end"] != null) {
                    $end = StringRepair($search["end"]);
                    $searchtxt['end'] = $end;
                }
            }
            if (isset($search["staff"])) {
                if ($search["staff"] != null) {
                    $staffid = StringRepair($search["staff"]);
                    $searchtxt['staffid'] = $staffid;
                }
            }
            if (isset($search["package"])) {
                if ($search["package"] != null) {
                    $source = StringRepair($search["package"]);
                    $searchtxt['source'] = $source;
                }
            }
        } else {
            if (!empty($this->session->userdata['packagereport']['start'])) {
                $start = StringRepair($this->session->userdata['packagereport']['start']);
                $searchtxt['start'] = $start;
            }
            if (!empty($this->session->userdata['packagereport']['end'])) {
                $end = StringRepair($this->session->userdata['packagereport']['end']);
                $searchtxt['end'] = $end;
            }
            if (!empty($this->session->userdata['packagereport']['staffid'])) {
                $staffid = StringRepair($this->session->userdata['packagereport']['staffid']);
                $searchtxt['staffid'] = $staffid;
            }
            if (!empty($this->session->userdata['packagereport']['source'])) {
                $source = StringRepair($this->session->userdata['packagereport']['source']);
                $searchtxt['source'] = $source;
            }
        }

        if ($searchtxt["start"] == "" && $searchtxt["end"] == "") {
            $searchtxt["start"] = date("Y-m-d");
            $searchtxt["end"] = date("Y-m-d");
        }
        $sql = "";
        if ($staffid != "" and is_numeric($staffid)) {
            $sql .= ' and t1.userid=' . $staffid;
        }
        if ($source != "" and is_numeric($source)) {
            $sql .= ' and t1.source=' . $source;
        }

        $msql = "";
        $cid = $this->session->userdata['logged_in']['companyid'];
        $managerid = $this->session->userdata['logged_in']['managerid'];
        if ($managerid > 0) {
            $msql = ' and t2.managerid=' . $managerid;
            $msql2 = ' and managerid=' . $managerid;
        }

        $query = "select t2.billno,t2.billdate,t1.sdate,t1.price,t1.qty,t1.amount,t4.package_name from " . TBL_INVOICE_ITEM . " as t1 left join " . TBL_INVOICE_MASTER . " as t2 on t2.id = t1.invid left join " . TBL_PACKAGE . " as t4 on t4.id = t1.source where t1.ctype=3 and t1.isdelete=0 $sql and  t1.sdate between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and t2.cid=" . $cid . $msql;
        $params['packlist'] = $this->Queries->getRecord($query);

        $query = "select id,person_name from " . TBL_USERINFO . " where isdelete=0 and user_type > 2 and cid=" . $cid . $msql2 . " order by id";
        $params['userlist'] = $this->Queries->get_tab_list($query, 'id', 'person_name');

        $query = "select id,package_name from " . TBL_PACKAGE . " where isdelete=0 and cid=" . $cid . $msql2 . " order by id ";
        $params['packagelist'] = $this->Queries->get_tab_list($query, 'id', 'package_name');

        $session_data = array(
            'start' => $searchtxt["start"],
            'end' => $searchtxt["end"],
            'staffid' => $searchtxt["staffid"],
            'source' => $searchtxt["source"]
        );
        // Add user data in session
        $this->session->set_userdata('packagereport', $session_data);

        $this->load->view('Report/Package', $params);
    }
    public function Expense()
    {
        if (!check_role_assigned('expensereport', 'view')) {
            $this->session->set_flashdata('error_msg', NOT_ALLOW_ACCESS);
            redirect(base_url('Dashboard'));
        }

        $searchtxt = array();


        $end = "";
        $start = "";
        $catid = "";
        $userid = "";
        $search = $this->input->post();

        if (isset($search["clearall"])) {
            $session_data = array(
                'start' => '',
                'end' => '',
                'catid' => '',
                'userid' => ''
            );
            $searchtxt['start'] = "";
            $searchtxt['end'] = "";
            $searchtxt['catid'] = "";
            $searchtxt['userid'] = "";
            // Add user data in session
            $this->session->set_userdata('expensereport', $session_data);
        } else if (isset($search["submit"])) {
            if (isset($search["start"])) {
                if ($search["start"] != null) {
                    $start = StringRepair($search["start"]);
                    $searchtxt['start'] = $start;
                }
            }
            if (isset($search["end"])) {
                if ($search["end"] != null) {
                    $end = StringRepair($search["end"]);
                    $searchtxt['end'] = $end;
                }
            }
            if (isset($search["cat"])) {
                if ($search["cat"] != null) {
                    $catid = StringRepair($search["cat"]);
                    $searchtxt['catid'] = $catid;
                }
            }
            if (isset($search["user"])) {
                if ($search["user"] != null) {
                    $userid = StringRepair($search["user"]);
                    $searchtxt['userid'] = $userid;
                }
            }
        } else {
            if (!empty($this->session->userdata['expensereport']['start'])) {
                $start = StringRepair($this->session->userdata['expensereport']['start']);
                $searchtxt['start'] = $start;
            }
            if (!empty($this->session->userdata['expensereport']['end'])) {
                $end = StringRepair($this->session->userdata['expensereport']['end']);
                $searchtxt['end'] = $end;
            }
            if (!empty($this->session->userdata['expensereport']['catid'])) {
                $catid = StringRepair($this->session->userdata['expensereport']['catid']);
                $searchtxt['catid'] = $catid;
            }
            if (!empty($this->session->userdata['expensereport']['userid'])) {
                $userid = StringRepair($this->session->userdata['expensereport']['userid']);
                $searchtxt['userid'] = $userid;
            }
        }

        if ($searchtxt["start"] == "" && $searchtxt["end"] == "") {
            $searchtxt["start"] = date("Y-m-d");
            $searchtxt["end"] = date("Y-m-d");
        }
        $sql = "";
        if ($catid != "" and is_numeric($catid)) {
            $sql .= ' and t1.catid=' . $catid;
        }
        if ($userid != "" and is_numeric($userid)) {
            $sql .= ' and t1.userid=' . $userid;
        }
        $msql = "";
        $cid = $this->session->userdata['logged_in']['companyid'];
        $managerid = $this->session->userdata['logged_in']['managerid'];
        if ($managerid > 0) {
            $msql = ' and t1.managerid=' . $managerid;
            $msql2 = ' and managerid=' . $managerid;
        }
        $query = "select t1.catid,t1.exp_date,t1.exp_type,t4.category,t1.description,t1.amount_paid,t2.paymod,t1.recipient_name,t3.person_name,t1.createdon from " . TBL_EXPENSE . " as t1 left join " . TBL_EXPENSE_CATEGORY . " as t4 on t4.id = t1.exp_type left join " . TBL_PAYMOD . " as t2 on t2.id = t1.paymod left join " . TBL_USERINFO . " as t3 on t3.id = t1.userid where t1.isdelete=0 $sql and  t1.exp_date between '" . $searchtxt['start'] . "' and '" . $searchtxt['end'] . "' and t1.cid=" . $cid . $msql;
        $params['expenselist'] = $this->Queries->getRecord($query);

        $query = "select id,person_name from " . TBL_USERINFO . " where isdelete=0 and user_type > 2 and cid=" . $cid . $msql2 . " order by id";
        $params['userlist'] = $this->Queries->get_tab_list($query, 'id', 'person_name');

        $params['catlist'] = array("" => "- Select -", "1" => "Shop Expense", "2" => "Staff Payment");

        $session_data = array(
            'start' => $searchtxt["start"],
            'end' => $searchtxt["end"],
            'catid' => $searchtxt["catid"],
            'userid' => $searchtxt["userid"]
        );
        // Add user data in session
        $this->session->set_userdata('expensereport', $session_data);
        $this->load->view('Report/Expense', $params);
    }

    public function Stock()
    {

        if (!check_role_assigned('stockreport', 'view')) {
            $this->session->set_flashdata('error_msg', NOT_ALLOW_ACCESS);
            redirect(base_url('Dashboard'));
        }

        $searchtxt = array();


        $msql2 = "";
        $cid = $this->session->userdata['logged_in']['companyid'];
        $managerid = $this->session->userdata['logged_in']['managerid'];
        if ($managerid > 0) {
            $msql2 = ' and managerid=' . $managerid;
        }

        $query = "select t1.prodname,t1.sale_price,COALESCE((select sum(qty) from " . TBL_STOCK . " where productid = t1.id and isdelete=0 and cid=" . $cid . $msql2  . " ),0) as currentstock,t1.minstock from " . TBL_PRODUCTS . " as t1 where t1.isdelete=0 and t1.cid=" . $cid . "";
        $params['stocklist'] = $this->Queries->getRecord($query);

        $session_data = array(
            'start' => $searchtxt["start"],
            'end' => $searchtxt["end"],
            'compid' => $searchtxt["compid"]
        );
        // Add user data in session
        $this->session->set_userdata('stockreport', $session_data);

        $this->load->view('Report/Stock', $params);
    }
    public function Enquiry()
    {
        if (!check_role_assigned('enquiryreport', 'view')) {
            $this->session->set_flashdata('error_msg', NOT_ALLOW_ACCESS);
            redirect(base_url('Dashboard'));
        }

        $searchtxt = array();

        $end = "";
        $start = "";

        $source = "";
        $search = $this->input->post();

        if (isset($search["clearall"])) {
            $session_data = array(
                'start' => '',

                'source' => ''
            );
            $searchtxt['start'] = "";
            $searchtxt['end'] = "";
            $searchtxt['staffid'] = "";
            $searchtxt['source'] = "";
            // Add user data in session
            $this->session->set_userdata('enquiryreport', $session_data);
        } else if (isset($search["submit"])) {
            if (isset($search["start"])) {
                if ($search["start"] != null) {
                    $start = StringRepair($search["start"]);
                    $searchtxt['start'] = $start;
                }
            }
            if (isset($search["end"])) {
                if ($search["end"] != null) {
                    $end = StringRepair($search["end"]);
                    $searchtxt['end'] = $end;
                }
            }

            if (isset($search["source"])) {
                if ($search["source"] != null) {
                    $source = $search["source"];
                    $searchtxt['source'] = $source;
                }
            }
        } else {
            if (!empty($this->session->userdata['enquiryreport']['start'])) {
                $start = StringRepair($this->session->userdata['enquiryreport']['start']);
                $searchtxt['start'] = $start;
            }
            if (!empty($this->session->userdata['enquiryreport']['end'])) {
                $end = StringRepair($this->session->userdata['enquiryreport']['end']);
                $searchtxt['end'] = $end;
            }
            if (!empty($this->session->userdata['enquiryreport']['staffid'])) {
                $staffid = StringRepair($this->session->userdata['enquiryreportt']['staffid']);
                $searchtxt['staffid'] = $staffid;
            }
            if (!empty($this->session->userdata['enquiryreport']['source'])) {
                $source = StringRepair($this->session->userdata['enquiryreport']['source']);
                $searchtxt['source'] = $source;
            }
        }

        if ($searchtxt["start"] == "" && $searchtxt["end"] == "") {
            $searchtxt["start"] = date("Y-m-d");
            $searchtxt["end"] = date("Y-m-d");
        }


        $msql = "";
        $cid = $this->session->userdata['logged_in']['companyid'];
        $managerid = $this->session->userdata['logged_in']['managerid'];
        if ($managerid > 0) {
            $msql = ' and managerid=' . $managerid;
        }


        $query = " select customername,mobile,enquiryfor,type,leadsource,leadstatus from " . TBL_ENQUIRY . " where isdelete=0 and leadsource='" . $searchtxt['source'] . "' and date between '" . $searchtxt["start"] . "' and '" . $searchtxt["end"] . "'  and cid=" . $cid . $msql;
        $params['enquirylist'] = $this->Queries->getRecord($query);
        $query = "select source from " . TBL_ENQUIRY_SOURCE . " where isdelete=0 order by priority desc";
        $params['sourcelist'] = $this->Queries->get_tab_list($query, 'source', 'source');
        $session_data = array(
            'start' => $searchtxt["start"],
            'end' => $searchtxt["end"],
            'source' => $searchtxt["source"]
        );
        // Add user data in session
        $this->session->set_userdata('enquiryreport', $session_data);

        $this->load->view('Report/Enquiry', $params);
    }
}
