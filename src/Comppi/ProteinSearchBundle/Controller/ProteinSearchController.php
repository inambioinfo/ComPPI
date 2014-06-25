<?php
namespace Comppi\ProteinSearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Comppi\ProteinSearchBundle\Entity\ProteinSearch;

class ProteinSearchController extends Controller
{
	private $speciesProvider = null;
	private $localizationTranslator = null;
	private $species_list = array(
		0 => 'H. sapiens',
		1 => 'D. melanogaster',
		2 => 'C. elegans',
		3 => 'S. cerevisiae'
	);
	private $majorloc_list = array (
		'cytoplasm' => 'Cytoplasm',
		'extracellular' => 'Extracellular Matrix',
		'mitochondrion' => 'Mitochondrion',
		'nucleus' => 'Nucleus',
		'membrane' => 'Plasma Membrane',
		'secretory-pathway' => 'Secretory Pathway',
	);
	private $minor_loc_abbr_patterns = array(
		'EXP',
		'IDA',
		'IPI',
		'IMP',
		'IGI',
		'IEP',
		'ISS',
		'ISO',
		'ISA',
		'ISM',
		'IGC',
		'IBA',
		'IBD',
		'IKR',
		'IRD',
		'RCA',
		'TAS',
		'NAS',
		'IC',
		'ND',
		'IEA',
		'NR',
		'SVM'
	);
	private $minor_loc_abbr_replacements = array(
		'Inferred from Experiment',
		'Inferred from Direct Assay',
		'Inferred from Physical Interaction',
		'Inferred from Mutant Phenotype',
		'Inferred from Genetic Interaction',
		'Inferred from Expression Pattern',
		'Inferred from Sequence or Structural Similarity',
		'Inferred from Sequence Orthology',
		'Inferred from Sequence Alignment',
		'Inferred from Sequence Model',
		'Inferred from Genomic Context',
		'Inferred from Biological aspect of Ancestor',
		'Inferred from Biological aspect of Descendant',
		'Inferred from Key Residues',
		'Inferred from Rapid Divergence',
		'inferred from Reviewed Computational Analysis',
		'Traceable Author Statement',
		'Non-traceable Author Statement',
		'Inferred by Curator',
		'No biological Data available',
		'Inferred from Electronic Annotation',
		'Not Recorded',
		'Support Vector Machine'
	);
	private $verbose = false;
	//private $verbose_log = array();
	private $uniprot_root = 'http://www.uniprot.org/uniprot/';
	private $exptype = array(
		0 => 'Unknown',
		1 => 'Experimental',
		2 => 'Predicted'
	);

	// PROTEIN SEARCH
	//public function proteinSearchAction($protein_name, $requested_species, $current_page)
	public function proteinSearchAction($keyword)
    {
		// $keyword is the way to handle protein_search/PROTEIN_NAME type requests
		// = protein name from URL hooked on protein search
		if (!empty($keyword))
		{
			$_POST['fProtSearchKeyword'] = $keyword;
		}
		
		$request_m = $this->get('request')->getMethod();

		$T = array(
            'ls' => array(),
			'keyword' => '',
			'result_msg' => '',
			'loc_treshold' => 0.0,
			'uniprot_root' => $this->uniprot_root
        );
		
		// PREPARE THE SEARCH FORM
		// species in the form
		foreach ($this->species_list as $sp_code => $sp_name)
		{
			$T['species_list'][$sp_code] = array(
				'code' => $sp_code,
				'name' => $sp_name,
				'checked' => true
			);
			if ($request_m=='POST' and !isset($_POST['fProtSearchSp'][(string)$sp_code]))
			{
				$T['species_list'][$sp_code]['checked'] = false;
			}
			// protein name from URL hooked on protein search
			if (!empty($keyword))
			{
				$_POST['fProtSearchSp'][$sp_code] = true;
			}
		}
		
		// major locs in the form
		foreach ($this->majorloc_list as $mloc_code => $mloc_name)
		{
			$T['majorloc_list'][$mloc_code] = array(
				'code' => $mloc_code,
				'name' => $mloc_name,
				'checked' => true
			);
			if ($request_m=='POST' and !isset($_POST['fProtSearchLoc'][(string)$mloc_code]))
			{
				$T['majorloc_list'][$mloc_code]['checked'] = false;
			}
			// protein name from URL hooked on protein search
			if (!empty($keyword))
			{
				$_POST['fProtSearchLoc'][$mloc_code] = true;
			}
		}
		
		
		// PROTEIN SEARCH SUBMITTED
		if ($request_m=='POST' or !empty($keyword)) {
			$DB = $this->getDbConnection();
			
			// PREPARE THE SEARCH CONDITIONS
			// SQL parameters: protein names as keywords
			$T['keyword'] = htmlspecialchars(strip_tags($_POST['fProtSearchKeyword']));
			$sql_cond_keywords = preg_split(
				"/\r\n|\n|\r/", // consider various platforms
				(isset($_POST['fProtSearchKeyword']) ? $_POST['fProtSearchKeyword'] : '')
			);
			if (!empty($sql_cond_keywords))
			{
				foreach ($sql_cond_keywords as $kk => $kwrd)
				{
					$sql_cond_keywords[$kk] = strtolower(trim($kwrd));
				}
			} else {
				$err[] = 'Please fill in a protein name.';
			}
			
			# SQL parameters: species
			$sql_cond_sp = [];
			if (!empty($_POST['fProtSearchSp']))
			{
				//$cond_sp = [];
				foreach ($_POST['fProtSearchSp'] as $fsp_code => $fsp_name)
				{
					if (isset($this->species_list[(int)$fsp_code]))
					{
						$sql_cond_sp[$fsp_code] = $fsp_code;
					}
				}
				//$sql_cond_sp = "'" . join("', '", $cond_sp) . "'";
			} else {
				$err[] = 'Please select at least one species.';
			}
			
			# SQL parameters: major localizations = compartments
			$sql_cond_mloc = [];
			if (!empty($_POST['fProtSearchLoc']))
			{
				foreach ($_POST['fProtSearchLoc'] as $fmloc_code => $fmloc_name)
				{
					if (isset($this->majorloc_list[$fmloc_code]))
					{
						$sql_cond_mloc[] = $fmloc_code; # discard keys!
					}
				}
				//$sql_cond_mloc = "'".join("', '", $cond_mloc)."'";
			} else {
				$err[] = 'Please select at least one subcellular compartment.';
			}
			
			# SQL parameters: localization treshold
			$loc_treshold = 0.0;
			if (!empty($_POST['fProtSearchLocScore']))
			{
				$T['loc_treshold'] = (int)$_POST['fProtSearchLocScore'];
				$loc_treshold = $_POST['fProtSearchLocScore']/100;
			}
			
			
			// check for validation errors
			if (!empty($err))
			{
				$err_msgs = implode(' ', $err);
				$this->get('session')->setFlash('ps-errors', $err_msgs);
				
				return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
			}
			
			
			// FIND THE PROTEIN IDS FROM THE MAIN PROTEINS, THE SYNONYMS AND THE LOCALIZATIONS
			// the old server can't handle complex mysql queries -> separate them
			// protein IDs from the strongest naming convention
			$r_prots_strongest = $DB->executeQuery(
				"SELECT id FROM Protein WHERE LOWER(proteinName) IN(?) AND specieId IN(?)",
				array($sql_cond_keywords, $sql_cond_sp),
				array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
			);
			if (!$r_prots_strongest)
				die('Protein IDs by strongest naming convention query failed!');
			$pids_by_strongest = $r_prots_strongest->fetchAll(\PDO::FETCH_COLUMN, 0);
			
			// protein IDs from the synonyms
			$r_name_to_prot = $DB->executeQuery(
				"SELECT proteinId FROM NameToProtein WHERE LOWER(name) IN(?) AND specieId IN(?)",
				array($sql_cond_keywords, $sql_cond_sp),
				array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
			);
			if (!$r_name_to_prot)
			{
				die('Protein IDs by synonyms query failed!');
			}
			$pids_by_n2p = $r_name_to_prot->fetchAll(\PDO::FETCH_COLUMN, 0);

			// protein IDs from the localizations table
			// filter only if *not* all major localizations are selected
			$pids_by_loc = [];
			if (!empty($sql_cond_mloc))
			{
				$sql_for_loc_filt = "
					SELECT proteinId
					FROM LocalizationScore
					WHERE  AND score > ?
				";
				$sql_cond_for_loc_filt = [];
				$sql_cond_val_for_loc_filt = [];
				$sql_cond_type_for_loc_filt = [];
				
				if (count($sql_cond_mloc)<count($this->majorloc_list))
				{
					$sql_cond_for_loc_filt[] = 'majorLocName IN(?)';
					$sql_cond_val_for_loc_filt[] = $sql_cond_mloc;
					$sql_cond_type_for_loc_filt[] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
				}
				
				if ($loc_treshold>0.0)
				{
					$sql_cond_for_loc_filt[] = 'score > ?';
					$sql_cond_val_for_loc_filt[] = strval($loc_treshold);
					$sql_cond_type_for_loc_filt[] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
				}
				
				if ($sql_cond_for_loc_filt && $sql_cond_val_for_loc_filt && $sql_cond_type_for_loc_filt)
				{
										$r_pids_by_loc = $DB->executeQuery(
						"SELECT proteinId FROM LocalizationScore WHERE ".implode(' AND ', $sql_cond_for_loc_filt),
						$sql_cond_val_for_loc_filt,
						$sql_cond_type_for_loc_filt
					);
					
					if (!$r_pids_by_loc)
					{
						die('Protein IDs by localization query failed!');
					}
					$pids_by_loc = $r_pids_by_loc->fetchAll(\PDO::FETCH_COLUMN, 0);
				}
			}
			
			
			// MERGE THE UNIQUE PROTEIN IDS = REQUESTED PROTEINS
			$prot_ids = array_unique(array_merge($pids_by_strongest, $pids_by_n2p, $pids_by_loc));

			
			// INTERACTORS PAGE / PROTEIN SELECTOR PAGE / NOT FOUND
			// only 1 protein ID = exact match -> display the interators page
			if (count($prot_ids)==1)
			{
				return $this->redirect($this->generateUrl(
					'ComppiProteinSearchBundle_interactors',
					array('comppi_id' => $prot_ids[0]))
				);
			}
			// multiple protein IDs -> display the intermediate page to select one
			elseif (count($prot_ids)>1)
			{
				$r_prots = $DB->executeQuery(
					"
						SELECT
							n2p.name, n2p.specieId, n2p.proteinId, n2p.namingConvention, p.proteinName
						FROM
							NameToProtein n2p, Protein p
						WHERE
								n2p.proteinId=p.id
							AND p.id IN(?)
						GROUP BY p.proteinName
						ORDER BY p.proteinName DESC
					",
					array($prot_ids),
					array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
				);
				if (!$r_prots)
					die('Protein search base on protein IDs failed!');
				
				while ($p = $r_prots->fetch(\PDO::FETCH_OBJ))
				{
					$pids[] = $p->proteinId;
					$T['ls'][] = array(
						'comppi_id' => $p->proteinId,
						'name' => $p->name,
						'name2' => $p->proteinName,
						'namingConvention' => $p->namingConvention,
						'species' => $this->species_list[(int)$p->specieId],
						'uniprot_link' => $this->uniprot_root.$p->proteinName
					);
				}
				// attach the full protein names to the list
				if (!empty($pids)) {
					$full_names = $this->getProteinSynonyms($pids);
					foreach ($T['ls'] as $i=>$vals) {
						$T['ls'][$i]['full_name'] = $full_names[$T['ls'][$i]['comppi_id']]['syn_fullname'];
					}
				} else {
					die("Protein IDs are missing for the full name query of the result selector!");
				}
				
				return $this->render(
					'ComppiProteinSearchBundle:ProteinSearch:middlepage.html.twig',
					$T
				);
			}
			// no proteins were found
			else
			{
				$T['result_msg'] = 'No proteins were found.';;
			}
		}
		
		return $this->render('ComppiProteinSearchBundle:ProteinSearch:index.html.twig', $T);
	}


	public function interactorsAction($comppi_id, $get_interactions)
	{
		$DB = $this->getDbConnection();
		$sp = $this->getSpeciesProvider();
		$spDescriptors = $sp->getDescriptors();
		$comppi_id = intval($comppi_id);
		$protein_ids = []; // collect the interactor IDs

		$T = array(
			'comppi_id' => $comppi_id,
			'ls' => array()
		);

		// details of the requested protein
		$T['protein'] = $this->getProteinDetails($comppi_id);

		// interactors
		$r_interactors = $DB->executeQuery("SELECT DISTINCT
				i.id AS iid, i.sourceDb, i.pubmedId,
				cs.score as confScore,
				p.id as pid, p.proteinName as name, p.proteinNamingConvention as namingConvention
			FROM Interaction i
			LEFT JOIN Protein p ON p.id=IF(actorAId = $comppi_id, i.actorBId, i.actorAId)
			LEFT JOIN ConfidenceScore cs ON i.id=cs.interactionId
			WHERE actorAId = $comppi_id OR actorBId = $comppi_id
			ORDER BY cs.score DESC");
		//	LIMIT ".$this->search_result_per_page);
		if (!$r_interactors)
			throw new \ErrorException('Interactor query failed!');

		$confScoreAvg = 0.0;
		// there may be a significant difference between
		// the number of proteins and the number of cycles (one protein multiple times?)
		// therefore a separate counter is needed
		$confCounter = 0;

		// interactors skeleton
		while ($i = $r_interactors->fetch(\PDO::FETCH_OBJ))
		{
			$T['ls'][$i->pid]['prot_id'] = $i->pid;
			$T['ls'][$i->pid]['prot_name'] = $i->name;
			$T['ls'][$i->pid]['prot_naming'] = $i->namingConvention;
			//if ($i->namingConvention=='UniProtKB-AC')
			$T['ls'][$i->pid]['uniprot_outlink'] = $this->uniprot_root.$i->name;
			$T['ls'][$i->pid]['confScore'] = round($i->confScore, 2)*100;
			$confScoreAvg += (float)$i->confScore;
			$confCounter++;

			$protein_ids[$i->pid] = $i->pid;
			$interaction_ids[$i->iid] = $i->iid;
		}

		// @TODO: letölthető dataset
		if ($get_interactions) {
			return $this->forward(
				'DownloadCenterBundle:DownloadCenter:serveInteractions',
				array('species' => array('abbr' => 'all', 'id' => -1),
					  'interaction_ids' => $interaction_ids)
			);
		}

		if (!empty($protein_ids)) {
			// localizations for the interactor
			$protein_locs = $this->getProteinLocalizations($protein_ids);

			// synonyms for the interactor
			$protein_synonyms = $this->getProteinSynonyms($protein_ids);

			// update the existing skeleton (therefore reference is needed)
			foreach($T['ls'] as $pid => &$actor)
			{
				// localizations to interactors
				if (!empty($protein_locs[$pid]))
					$actor['locs'] = $protein_locs[$pid];
				// synonyms to interactors
				if (!empty($protein_synonyms[$pid]['syn_fullname']))
					$actor['syn_fullname'] =  $protein_synonyms[$pid]['syn_fullname'];
				if (!empty($protein_synonyms[$pid]['synonyms']))
					$actor['synonyms'] = $protein_synonyms[$pid]['synonyms'];
				//$actor['syn_namings'] = (empty($protein_synonyms[$pid]['syn_namings']) ? array() : $protein_synonyms[$pid]['syn_namings']);
			}
		}

		$T['protein']['interactionNumber'] = count($protein_ids);
		if ($T['protein']['interactionNumber']) {
			$T['protein']['avgConfScore'] = round($confScoreAvg/$confCounter, 2)*100;
		} else {
			$T['protein']['avgConfScore'] = false;
		}

		return $this->render('ComppiProteinSearchBundle:ProteinSearch:interactors.html.twig',$T);
	}


	public function autocompleteAction($keyword)
	{
		$DB = $this->getDbConnection();
		$r_i = $DB->executeQuery("SELECT name FROM ProteinName WHERE name LIKE ? ORDER BY LENGTH(name) LIMIT 15", array("%$keyword%"));
		if (!$r_i) throw new \ErrorException('Autocomplete query failed!');

		$list = array();
		while ($p = $r_i->fetch(\PDO::FETCH_OBJ))
			$list[] = $p->name;

        return new Response(json_encode($list));
	}


	private function getProteinDetails($comppi_id)
	{
		$DB = $this->getDbConnection();
		$r_p = $DB->executeQuery("SELECT proteinName AS name, proteinNamingConvention AS naming, specieId FROM Protein WHERE id=?", array($comppi_id));
		if (!$r_p) throw new \ErrorException('Protein query failed!');

		$prot_details = $r_p->fetch(\PDO::FETCH_ASSOC);
		$prot_details['species'] = $prot_details['specieId']; // @TODO: map name to id
		$prot_details['locs'] = $this->getProteinLocalizations(array($comppi_id));
		$prot_details['locs'] = (!empty($prot_details['locs'][$comppi_id]) ? $prot_details['locs'][$comppi_id] : array());

		$syns = $this->getProteinSynonyms(array($comppi_id));
		$prot_details['synonyms'] = $syns[$comppi_id]['synonyms'];
		$prot_details['fullname'] = (!empty($syns[$comppi_id]['syn_fullname']) ? $syns[$comppi_id]['syn_fullname'] : '');
		$prot_details['uniprot_link'] = $this->uniprot_root.$prot_details['name'];

		return $prot_details;
	}


	// @var array The list of comppi ids
	private function getProteinLocalizations($comppi_ids)
	{
		$DB = $this->getDbConnection();

		$sql_ls = 'SELECT
				proteinId as pid, majorLocName, score
			FROM LocalizationScore
			WHERE
				proteinId IN ('.join(',', $comppi_ids).')';
		$this->verbose ? $this->verbose_log[] = $sql_ls : '';

		if (!$r_ls = $DB->executeQuery($sql_ls))
			die('LocalizationScore query failed!');

		$loc_scores = array();
		while ($ls = $r_ls->fetch(\PDO::FETCH_OBJ))
		{
			$loc_scores[$ls->pid][$ls->majorLocName] = $ls->score;
		}

		$sql_pl = 'SELECT
				ptl.proteinId as pid, ptl.localizationId AS locId, ptl.sourceDb, ptl.pubmedId,
				lt.name as minorLocName, lt.goCode, lt.majorLocName,
				st.name AS exp_sys, st.confidenceType AS exp_sys_type
			FROM ProtLocToSystemType pltst, SystemType st, ProteinToLocalization ptl
			LEFT JOIN Loctree lt ON ptl.localizationId=lt.id
			WHERE ptl.id=pltst.protLocId
				AND pltst.systemTypeId=st.id
				AND ptl.proteinId IN ('.join(',', $comppi_ids).')';
		$this->verbose ? $this->verbose_log[] = $sql_pl : '';

		if (!$r_pl = $DB->executeQuery($sql_pl))
			die('ProteinToLocalization query failed!');

		$i = 0;
		while ($p = $r_pl->fetch(\PDO::FETCH_OBJ))
		{
			$i++;
			$mnlrc = 0; // minor loc replacement count

			$pl[$p->pid][$i]['source_db'] = $p->sourceDb;
			$pl[$p->pid][$i]['pubmed_link'] = $this->linkToPubmed($p->pubmedId);
			// loc exp sys type replacement: IPI -> IPI: Inferred From Physical Interaction
			$loc_exp_sys = str_replace(
				$this->minor_loc_abbr_patterns,
				$this->minor_loc_abbr_replacements,
				$p->exp_sys,
				$mnlrc
			);
			if ($mnlrc) {
				$pl[$p->pid][$i]['loc_exp_sys'] = $this->exptype[$p->exp_sys_type]
					.': '.$p->exp_sys
					.' <span class="infobtn" title="'
					.$p->exp_sys.': '.$loc_exp_sys.
					'"> ? </span>';
			} else {
				$pl[$p->pid][$i]['loc_exp_sys'] = $this->exptype[$p->exp_sys_type]
					.': '.$p->exp_sys;
			}
			$pl[$p->pid][$i]['loc_exp_sys_type'] = $p->exp_sys_type;
			if (!empty($p->minorLocName)) {
				$pl[$p->pid][$i]['small_loc'] = ucfirst($p->minorLocName);
				$pl[$p->pid][$i]['go_code'] = ucfirst($p->goCode);
			} else {
				$pl[$p->pid][$i]['small_loc'] = 'N/A';
				$pl[$p->pid][$i]['go_code'] = 'N/A';
			}
			if (!empty($p->majorLocName)) {
				$pl[$p->pid][$i]['large_loc'] = ucfirst($p->majorLocName);
				if (!empty($loc_scores[$p->pid][$p->majorLocName])) {
					$pl[$p->pid][$i]['loc_score'] = round($loc_scores[$p->pid][$p->majorLocName], 2)*100;
				} else {
					$pl[$p->pid][$i]['loc_score'] = 0;
				}
			} else {
				$pl[$p->pid][$i]['large_loc'] = 'N/A';
				$pl[$p->pid][$i]['loc_score'] = 0;
			}
		}
		$this->verbose ? $this->verbose_log[] = count($pl).' protein locations found' : '';

		return (!empty($pl) ? $pl : array());
	}


	/*	Collect the synonyms for a given set of proteins, and group these by protein ID.

		@param $comppi_ids: array of ints, the protein IDs
		@return array

		Example:
		>>> this->getProteinSynonyms($comppi_ids)
		... synonyms = array(
				'syn_fullname' = 'UniprotFull name',
				'synonyms' => array('Syn1', 'syn2')
			)
	*/
	private function getProteinSynonyms($comppi_ids)
	{
		$DB = $this->getDbConnection();
		$sql_syn = "SELECT proteinId AS pid, name, namingConvention FROM NameToProtein WHERE proteinId IN(".join(',', $comppi_ids).")";
		$this->verbose ? $this->verbose_log[] = $sql_syn : '';

		if (!$r_syn = $DB->executeQuery($sql_syn))
			throw new \ErrorException('getProteinSynonyms query failed!');

		$syns = array();
		while ($s = $r_syn->fetch(\PDO::FETCH_OBJ))
		{
			if ($s->namingConvention=='UniProtFull') {
				$syns[$s->pid]['syn_fullname'] = $s->name; // full name highlighted...
			} else {
				$syns[$s->pid]['synonyms'][] = $s->name.'&nbsp;('.$s->namingConvention.')';
			}
		}

		return $syns;
	}


	private function linkToPubmed($pubmed_uid)
	{
		return 'http://www.ncbi.nlm.nih.gov/pubmed/'.$pubmed_uid;
	}

	private function initKeyword($protein_name)
	{

		// $request->request->get('fProtSearchKeyword') is not empty even if no keyword was filled in!
		if (!empty($_POST['fProtSearchKeyword']))
		{
			$keyword = $_POST['fProtSearchKeyword'];
		}
		else if (!empty($protein_name))
		{
			$keyword = $protein_name;
		}
		// Form was submitted, but we haven't had any keyword
		elseif (isset($_SESSION['protein_search_keyword']))
		{
			//$this->get('session')->getFlashBag()->add('no_keyword_err', 'Please fill in a keyword!');
			$keyword = $_SESSION['protein_search_keyword'];
		}
		else
		{
			$keyword = '';
		}
		return $keyword;
	}

	/*
		@var $requested_species the list of species abbreviations separated by commas, e.g. hs,ce
	*/
	private function initSpecies($requested_species = '')
	{
		$species_provider = $this->getSpeciesProvider();

		if (!empty($_POST['fProtSearchSpecies'])) {
			// this ensures that we need an exact match from the input to be valid
			// if we don't get back an object, then the form was forged
			$o_sp_descriptor = $species_provider->getSpecieByAbbreviation($_POST['fProtSearchSpecies']);
			$species_id = $o_sp_descriptor->id;
		} elseif (!empty($requested_species)) {
			$o_sp_descriptor = $species_provider->getSpecieByAbbreviation($requested_species);
			$species_id = $o_sp_descriptor->id;
		} else {
			$species_id = 0; // human
		}

		// add the taxonomical abbreviations of all species, they'll be needed on the species selector buttons
		$descriptors = $species_provider->getDescriptors();
		foreach($descriptors as $o)
		{
			$o->shortname = substr_replace($o->name, '. ', 1, strpos($o->name, ' '));
		}

		return $species_id;
	}

	private function initPageNum($curr_page)
	{
		$page = (preg_match('/^[0-9][0-9]*$/', $curr_page) ? (int)$curr_page : 0);
		$this->search_range_start = $page * $this->search_result_per_page;

		return $page;
	}

	private function getSpeciesProvider()
	{
		if (!$this->speciesProvider)
			$this->speciesProvider = $this->get('comppi.build.specieProvider');

		return $this->speciesProvider;
	}

	private function getDbConnection()
	{
		if (empty($this->DB))
			$this->DB = $this->get('database_connection');

		$this->get('database_connection')->getConfiguration()->setSQLLogger(null);
		return $this->DB;
	}

	private function getLocalizationTranslator()
	{
		if (!$this->localizationTranslator)
			$this->localizationTranslator = $this->get('comppi.build.localizationTranslator');

		return $this->localizationTranslator;
	}


	/**
	 * Current test protein: P04637 = ComPPI ID: 17387
	 * Gets the first neighbours of a node with their connections to earch other (or optionally all of their interactions). Writes a tab-separated text file with rows like: "locA.nodeA\tlocB.nodeB\n".
	 * @param int $comppi_id The ComPPI ID of the starting node */
	public function subgraphAction($comppi_id)
	{
		$joined_node_names = true; // node name = major_loc.protein_name (to display in Cytoscape for example)
		$interaction_count = 0;

		$interaction_sql = "INSERT INTO Interaction (id, actorAId, actorBId) VALUES \n";
		$interaction_rows = array();
		$protein_sql = "INSERT INTO Protein (id, specieId, proteinName) VALUES \n";
		$protein_rows = array();
		$prot_to_loc_sql = "INSERT INTO ProteinToLocalization (id, proteinId, localizationId) VALUES \n";
		$prot_to_loc_rows = array();
		$protloc_to_systype_sql = "INSERT INTO ProtLocToSystemType (protLocId, systemTypeId) VALUES \n";
		$protloc_to_systype_rows = array();
		$systype_sql = "INSERT INTO SystemType (id, confidenceType) VALUES \n";
		$systype_rows = array();

		// GET THE STAR-SHAPED NETWORK OF THE REQUESTED PROTEIN AND ITS FIRST NEIGHBOURS
		$DB = $this->getDbConnection();
		$r_actor_ids = $DB->executeQuery(
			"SELECT DISTINCT
				i.id AS iid,
				IF(actorAId=?, actorBId, actorAId) as actorId,
				p.id as proteinId, p.proteinName
			FROM Interaction i
			LEFT JOIN Protein p ON p.id=IF(actorAId = ?, i.actorBId, i.actorAId)
			WHERE actorAId = ? OR actorBId = ?",
			array($comppi_id, $comppi_id, $comppi_id, $comppi_id)
		);

		while($actor = $r_actor_ids->fetch(\PDO::FETCH_OBJ)) {
			$first_neighbour_ids[$actor->actorId] = $actor->actorId;
			$interaction_rows[$actor->iid] = '('.$actor->iid.', '.$comppi_id.', '.$actor->actorId.')';
			$protein_rows[$actor->proteinId] = '('.$actor->proteinId.", 0, '".$actor->proteinName."')";
		}

		//die(var_dump($first_neighbours));

		// GET THE INTERACTIONS AMONG THE FIRST NEIGHBOURS
		$sql_neighbour_links = "SELECT DISTINCT i.id as iid, p1.id as p1id, p1.proteinName as proteinA, p2.id as p2id, p2.proteinName as proteinB, ptl1.id as protLocAId, ptl1.localizationId as locAId, ptl2.id as protLocBId, ptl2.localizationId as locBId, st1.id as sysTypeAId, st1.confidenceType as confTypeA, st2.id as sysTypeBId, st1.confidenceType as confTypeB
		FROM Interaction i
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN ProteinToLocalization ptl1 ON p1.id=ptl1.proteinId
		LEFT JOIN ProtLocToSystemType ptst1 ON ptl1.id=ptst1.protLocId
		LEFT JOIN SystemType st1 ON ptst1.systemTypeId=st1.id
		LEFT JOIN Protein p2 ON p2.id=i.actorBId
		LEFT JOIN ProteinToLocalization ptl2 ON p2.id=ptl2.proteinId
		LEFT JOIN ProtLocToSystemType ptst2 ON ptl2.id=ptst2.protLocId
		LEFT JOIN SystemType st2 ON ptst2.systemTypeId=st2.id
		WHERE (i.actorAId IN(".join(',', $first_neighbour_ids).") OR i.actorBId IN(".join(',', $first_neighbour_ids)."))
		GROUP BY ptl1.localizationId, ptl2.localizationId";

		$sql_neighbour_links_for_negative_set = "SELECT DISTINCT i.id as iid, p1.id as p1id, p1.proteinName as proteinA, p2.id as p2id, p2.proteinName as proteinB, ptl1.id as protLocAId, ptl1.localizationId as locAId, ptl2.id as protLocBId, ptl2.localizationId as locBId, st1.id as sysTypeAId, st1.confidenceType as confTypeA, st2.id as sysTypeBId, st1.confidenceType as confTypeB
		FROM Interaction i
		LEFT JOIN Protein p1 ON p1.id=i.actorAId
		LEFT JOIN ProteinToLocalization ptl1 ON p1.id=ptl1.proteinId
		LEFT JOIN ProtLocToSystemType ptst1 ON ptl1.id=ptst1.protLocId
		LEFT JOIN SystemType st1 ON ptst1.systemTypeId=st1.id
		LEFT JOIN Protein p2 ON p2.id=i.actorBId
		LEFT JOIN ProteinToLocalization ptl2 ON p2.id=ptl2.proteinId
		LEFT JOIN ProtLocToSystemType ptst2 ON ptl2.id=ptst2.protLocId
		LEFT JOIN SystemType st2 ON ptst2.systemTypeId=st2.id
		WHERE (i.actorAId IN(".join(',', $first_neighbour_ids).") OR i.actorBId IN(".join(',', $first_neighbour_ids)."))
		GROUP BY ptl1.localizationId, ptl2.localizationId LIMIT 20000";

		//die($sql_neighbour_links);

		$r_neighbour_links = $DB->executeQuery($sql_neighbour_links);

		$locs = $this->getLocalizationTranslator();

		while($link = $r_neighbour_links->fetch(\PDO::FETCH_OBJ)) {
			$large_loc_a = (empty($link->locAId) ? "N/A" : $locs->getLargelocById($link->locAId));
			$large_loc_b = (empty($link->locBId) ? "N/A" : $locs->getLargelocById($link->locBId));

			// take those and only those interactions,
			// where the interactors are in the same known localization -> POSITIVE DATA SET
			// for NEGATIVE DATA SET: $large_loc_a != $large_loc_b
			if ($large_loc_a == $large_loc_b AND $large_loc_a != 'N/A') {
				$interaction_count++;

				// Interaction
				$interaction_tmp_row = '('.$link->iid.', '.$link->p1id.', '.$link->p2id.')';
				if (!in_array($interaction_tmp_row, $interaction_rows))
					$interaction_rows[$link->iid] = $interaction_tmp_row;

				// Protein
				$protein_rows[$link->p1id] = '('.$link->p1id.", 0, '".$link->proteinA."')";
				$protein_rows[$link->p2id] = '('.$link->p2id.", 0, '".$link->proteinB."')";

				// ProteinToLocalization
				$prot_to_loc_rows[$link->protLocAId] = '('.$link->protLocAId.', '.$link->p1id.', '.$link->locAId.')';
				$prot_to_loc_rows[$link->protLocBId] = '('.$link->protLocBId.', '.$link->p2id.', '.$link->locBId.')';

				// ProtLocToSystemType
				$protloc_to_systype_rows[$link->protLocAId] = '('.$link->protLocAId.', '.$link->sysTypeAId.')';
				$protloc_to_systype_rows[$link->protLocBId] = '('.$link->protLocBId.', '.$link->sysTypeBId.')';

				// SystemType
				$systype_rows[$link->sysTypeAId] = '('.$link->sysTypeAId.', '.$link->confTypeA.')';
				$systype_rows[$link->sysTypeBId] = '('.$link->sysTypeBId.', '.$link->confTypeB.')';
			}
		}

		$interaction_sql .= join(", \n", $interaction_rows);
		$protein_sql .= join(", \n", $protein_rows);
		$prot_to_loc_sql .= join(", \n", $prot_to_loc_rows);
		$protloc_to_systype_sql .= join(", \n", $protloc_to_systype_rows);
		$systype_sql .= join(", \n", $systype_rows);

		$file_tbl_structure = "/var/www/comppi/comppi_positive_dataset_structure-trimmed.sql";
		$fp_structure = fopen($file_tbl_structure, "r");
		$tbl_structures = fread($fp_structure, filesize($file_tbl_structure));
		fclose($fp_structure);

		$filename = date("YmdHis").'_subgraph_of_'.$comppi_id.".sql";
		$fp = fopen("/var/www/comppi/$filename", "a");
		fwrite($fp,
			 $tbl_structures."\n"
			.$interaction_sql.";\n\n"
			.$protein_sql.";\n\n"
			.$prot_to_loc_sql.";\n\n"
			.$protloc_to_systype_sql.";\n\n"
			.$systype_sql.";\n\n"
		);
		fclose($fp);
		chmod("/var/www/comppi/$filename", 0777);

		return new Response("[ OK ]<br>First neighbours: ".$r_actor_ids->rowCount()."<br>Interactions: $interaction_count");
	}
}
