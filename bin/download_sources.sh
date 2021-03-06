#!/bin/bash

# Downloads database sources from vendors

bin_dir="$( cd "$( dirname "$0" )" && pwd )"

if [ ! -d $bin_dir/../databases ] 
then
    mkdir -p $bin_dir/../databases
fi

cd $bin_dir/../databases

# Checks for comppi custom mappings
# $1: specie abbr.
# $2: map name
check_comppi_custom () {
    if [ ! -f $2 ]
    then
        wget "http://bificomp2.sote.hu/~erenon/comppi-custom-mapping/$1/$2"
    fi
}

###
# Homo Sapiens
###

if [ ! -d ./hs ] 
then
    mkdir -p ./hs
fi

cd ./hs

### Localizations

if [ ! -d ./localization ] 
then
    mkdir -p ./localization
fi

cd ./localization

# Bacello
#if [ ! -f pred_homo ]
#then
#    wget http://gpcr.biocomp.unibo.it/bacello/datasets/H.sapiens_annotations.zip -O ./bacello.zip
#    unzip ./bacello.zip
#    rm bacello.zip
#fi   

# eSLDB
if [ ! -f eSLDB_Homo_sapiens.txt ]
then
    wget --post-data 'organism=Homo+sapiens&checkbox1_1=SI&checkbox1_2=SI&checkbox1_8=SI&f_type=plain&download=Download' \
    http://gpcr.biocomp.unibo.it/cgi-bin/predictors/esldb/full_download.cgi \
    -O ./eSLDB_Homo_sapiens.txt
fi

# GO
if [ ! -f go_hs.csv ]
then
    wget ftp://ftp.ebi.ac.uk/pub/databases/GO/goa/HUMAN/gene_association.goa_human.gz
    gzip -d -N gene_association.goa_human.gz
    mv gene_association.goa_human go_hs.csv
fi    

# Human proteinpedia
if [ ! -f HUPA_Localization_Data.txt ]
then
    wget http://www.humanproteinpedia.org/HuPA_Download/FULL/HUPA_Localization_Data.txt.gz -O ./hupa.gz
    gzip -d -N ./hupa.gz
    rm ./hupa.gz
fi


# LOCATE
if [ ! -f LOCATE_human_v6_20081121.xml ]   
then
    wget http://locate.imb.uq.edu.au/info_files/LOCATE_human_v6_20081121.xml.zip -O ./locatexml.zip
    unzip locatexml.zip
    rm locatexml.zip
fi   

# MatrixDB Loc
if [ ! -f ECM_Protein_list.csv ]
then
    wget http://matrixdb.ibcp.fr/download/proteins_ECM_20140320.csv -O ECM_Protein_list.csv
fi

if [ ! -f Secreted_Protein_list.csv ]
then
    wget http://matrixdb.ibcp.fr/download//proteins_Secreted_20140320.csv -O Secreted_Protein_list.csv
fi

if [ ! -f Membrane_Protein_list.csv ]
then
    wget http://matrixdb.ibcp.fr/download//proteins_Membrane_20140320.csv -O Membrane_Protein_list.csv
fi

# Organelle
if [ ! -f organelle_hs.txt ]
then
    wget "http://organelledb.lsi.umich.edu/search-result.php?local=all&endoplasmic+reticulum=all&membrane+protein=all&miscellaneous+others=all&mitochondrion=all&nucleus=all&protein+complex=all&organism=human&searchtype=full&geneAdv=&flat_file=Download+in+Flat+File" -O ./organelle_hs.txt
fi  

# Pagosub
if [ ! -f homo_sapiens.csv ]
then
    wget http://webdocs.cs.ualberta.ca/%7Ebioinfo/PA/GOSUB/data/animal/homo_sapiens_csv.zip -O ./pagosub.zip
    unzip pagosub.zip
    rm pagosub.zip
fi

# Human Protein Atlas
if [ ! -f subcellular_location.csv ]
then
    wget http://www.proteinatlas.org/download/subcellular_location.csv.zip -O ./proteinAtlas.zip
    unzip proteinAtlas.zip
    rm proteinAtlas.zip
fi

### Interactions

if [ ! -d ../interaction ] 
then
    mkdir -p ../interaction
fi

cd ../interaction

# Biogrid
# @see at the end of this file

# CCSB
if [ ! -f HI2_2011.tsv ]
then
    wget http://interactome.dfci.harvard.edu/H_sapiens/download/HI2_2011.tsv
fi   

# DiP
# @see at the end of this file

# HomoMINT
#if [ ! -f homomint-full.mitab25.txt-binary.mitab26.txt ]
#then
#    wget ftp://mint.bio.uniroma2.it/pub/release/homomint/homomint-full.mitab25.txt-binary.mitab26.txt
#fi

# HPRD
if [ ! -f BINARY_PROTEIN_PROTEIN_INTERACTIONS.txt ]
then
    # download zip
    wget \
    -U "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.14) Gecko/20080418 Ubuntu/7.10 (gutsy) Firefox/2.0.0.14" \
    http://www.hprd.org/edownload/HPRD_Release9_041310 \
    -O ./hprd.tar.gz
    
    tar xvfz hprd.tar.gz HPRD_Release9_062910/BINARY_PROTEIN_PROTEIN_INTERACTIONS.txt
    
    mv HPRD_Release9_062910/BINARY_PROTEIN_PROTEIN_INTERACTIONS.txt ./BINARY_PROTEIN_PROTEIN_INTERACTIONS.txt
    rm -r HPRD_Release9_062910
    
    # cleanup
    rm hprd.tar.gz
fi

# IntAct
# @see at the end of this file   

# Matrix
if [ ! -f MatrixDB_20120420.txt ]
then
    wget http://matrixdb.ibcp.fr/download/MatrixDB_latest.tab.gz -O ./matrix.gz
    gzip -d -N ./matrix.gz
    rm ./matrix.gz
fi

# MINT
if [ ! -f 2013-03-26-mint-human-binary.mitab26.txt ]
then
    wget "ftp://mint.bio.uniroma2.it/pub/release/mitab26/current/2013-03-26-mint-human-binary.mitab26.txt"
fi

# MIPS
if [ ! -f allppis.xml ]
then
    wget http://mips.helmholtz-muenchen.de/proj/ppi/data/mppi.gz -O ./mips.gz
    gzip -d -N ./mips.gz
    rm ./mips.gz
fi

# PIP's
#if [ ! -f PredictedInteractions1000.txt ]
#then
#    wget http://www.compbio.dundee.ac.uk/www-pips/downloads/PredictedInteractions1000.txt
#fi 

### Maps

if [ ! -d ../map ] 
then
    mkdir -p ../map
fi

cd ../map

check_comppi_custom hs comppi_human_ensemblgeneid-uniprotkbac.txt
check_comppi_custom hs comppi_human_ensemblpeptideid-uniprotkbac.txt
check_comppi_custom hs comppi_human_entrezgene-uniprotkbac.txt
check_comppi_custom hs comppi_human_entrezprotein-uniprotkbac.txt
check_comppi_custom hs comppi_human_genebankproteinid-uniprotkbac.txt
check_comppi_custom hs comppi_human_refseq-uniprotkbac.txt
check_comppi_custom hs comppi_human_uniprotgenename-uniprotkbac.txt
check_comppi_custom hs human_organelle.csv
check_comppi_custom hs comppi_uniprot_deleted_hs.txt
check_comppi_custom hs comppi_hs_uniprotkb-ac_uniprotkb-swissprot.txt
check_comppi_custom hs comppi_hs_uniprotkb-ac_uniprotkb-trembl.txt


# Uniprot
if [ ! -f HUMAN_9606_idmapping_selected.tab ]
then
    wget "ftp://ftp.uniprot.org/pub/databases/uniprot/current_release/knowledgebase/idmapping/by_organism/HUMAN_9606_idmapping_selected.tab.gz" -O ./uniprot.gz
    gzip -d -N uniprot.gz
    rm uniprot.gz
fi

# UniprotFullname
if [ ! -f uniprot_hs_fullname.tab ] 
then
    wget "http://www.uniprot.org/uniprot/?query=organism%3a%22Homo+sapiens+%5b9606%5d%22&force=yes&format=tab&columns=id,entry%20name,reviewed,protein%20names" -O ./uniprot_hs_fullname.tab
fi

# UniprotSecondary
if [ ! -f sec_ac.txt ]
then
    wget "ftp://ftp.uniprot.org/pub/databases/uniprot/knowledgebase/docs/sec_ac.txt" -O ./sec_ac.txt
fi

# Hprd
if [ ! -f HPRD_ID_MAPPINGS.txt ]
then
     # download zip
     wget \
    -U "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.14) Gecko/20080418 Ubuntu/7.10 (gutsy) Firefox/2.0.0.14" \
    http://www.hprd.org/edownload/HPRD_FLAT_FILES_041310 \
    -O ./hprd.tar.gz
    
    tar xvfz hprd.tar.gz FLAT_FILES_072010/HPRD_ID_MAPPINGS.txt
    
    mv FLAT_FILES_072010/HPRD_ID_MAPPINGS.txt ./HPRD_ID_MAPPINGS.txt
    rm -r FLAT_FILES_072010
    
    # cleanup
    rm hprd.tar.gz
fi

# Netbiol refseq->uniprot
if [ ! -f comppi_refseq_uniprot.csv ]
then
    echo "RefSeq->UniProtKB-AC map not found, upload manually as comppi_refseq_uniprot.csv"
fi 

cd ../../

###
# Drosophila Melanogaster 
###

if [ ! -d ./dm ] 
then
    mkdir -p ./dm
fi

cd ./dm

### Localizations

if [ ! -d ./localization ] 
then
    mkdir -p ./localization
fi

cd ./localization

# eSLDB
if [ ! -f Drosophila_melanogaster.BDGP5.4.49.pep.all.fa_subloc ]
then
    wget http://gpcr.biocomp.unibo.it/esldb/update/Drosophila_melanogaster.BDGP5.4.49.pep.all.fa_subloc
fi  

# GO
if [ ! -f go_dm.csv ]
then
    wget "ftp://ftp.ebi.ac.uk/pub/databases/GO/goa/FLY/gene_association.goa_fly.gz"
    gzip -d -N gene_association.goa_fly.gz
    mv gene_association.goa_fly go_dm.csv
fi  

# PaGOSUB
if [ ! -f drosophila_melanogaster.csv ]
then
    wget http://webdocs.cs.ualberta.ca/%7Ebioinfo/PA/GOSUB/data/animal/drosophila_melanogaster_csv.zip -O ./pagosub.zip
    unzip ./pagosub.zip
    rm ./pagosub.zip
fi   

# Organelle
if [ ! -f organelle_dm.txt ]
then
    wget "http://organelledb.lsi.umich.edu/search-result.php?local=all&endoplasmic+reticulum=all&membrane+protein=all&miscellaneous+others=all&mitochondrion=all&nucleus=all&protein+complex=all&organism=fly&searchtype=full&geneAdv=&flat_file=Download+in+Flat+File" -O ./organelle_dm.txt
fi 

### Interactions

if [ ! -d ../interaction ] 
then
    mkdir -p ../interaction
fi

cd ../interaction 

# Biogrid
# @see at the end of this file 

# DiP
# @see at the end of this file

# DroID

if [ ! -f finley_yth.txt ]
then
    wget http://www.droidb.org/data/DroID_v2014_01/finley_yth.txt
fi

if [ ! -f CURAGEN_YTH.txt ]
then
    wget http://www.droidb.org/data/DroID_v2014_01/curagen_yth.txt -O CURAGEN_YTH.txt
fi

if [ ! -f HYBRIGENICS_YTH.txt ]
then
    wget http://www.droidb.org/data/DroID_v2014_01/hybrigenics_yth.txt -O HYBRIGENICS_YTH.txt
fi

if [ ! -f DPIM_COAPCOMPLEX.txt ]
then
    wget http://www.droidb.org/data/DroID_v2014_01/dpim_coapcomplex.txt -O DPIM_COAPCOMPLEX.txt
fi

if [ ! -f FLY_OTHER_PHYSICAL.txt ]
then
    wget http://www.droidb.org/data/DroID_v2014_01/fly_other_physical.txt -O FLY_OTHER_PHYSICAL.txt
fi

# IntAct
# @see at the end of this file

# MINT
if [ ! -f 2013-03-26-mint-Drosophila-binary.mitab26.txt ]
then
    wget "ftp://mint.bio.uniroma2.it/pub/release/mitab26/current/2013-03-26-mint-Drosophila-binary.mitab26.txt"
fi

### Maps

if [ ! -d ../map ] 
then
    mkdir -p ../map
fi

cd ../map

check_comppi_custom dm comppi_drosi_ensemblgeneid-uniprotkbac.txt
check_comppi_custom dm comppi_drosi_ensemblpeptideid-uniprotkbac.txt
check_comppi_custom dm comppi_drosi_enterzgene-uniprotkbac.txt
check_comppi_custom dm comppi_drosi_refseq-uniprotkbac.txt
check_comppi_custom dm comppi_drosi_uniparc-uniprotkbac.txt
check_comppi_custom dm drosi_organelle.csv
check_comppi_custom dm comppi_uniprot_deleted_dm.txt
check_comppi_custom dm comppi_dm_uniprotkb-ac_uniprotkb-swissprot.txt
check_comppi_custom dm comppi_dm_uniprotkb-ac_uniprotkb-trembl.txt

# Uniprot
if [ ! -f DROME_7227_idmapping_selected.tab ]
then
    wget "ftp://ftp.uniprot.org/pub/databases/uniprot/current_release/knowledgebase/idmapping/by_organism/DROME_7227_idmapping_selected.tab.gz" -O ./uniprot.gz
    gzip -d -N uniprot.gz
    rm uniprot.gz
fi 

# UniprotFullname
if [ ! -f uniprot_dm_fullname.tab ] 
then
    wget "http://www.uniprot.org/uniprot/?query=organism%3a%22Drosophila+melanogaster+%5b7227%5d%22&force=yes&format=tab&columns=id,entry%20name,reviewed,protein%20names" -O ./uniprot_dm_fullname.tab
fi

# UniprotSecondary
if [ ! -f sec_ac.txt ]
then
    ln -s ../../hs/map/sec_ac.txt sec_ac.txt
fi

# Netbiol refseq->uniprot
if [ ! -f comppi_refseq_uniprot.csv ]
then
    echo "RefSeq->UniProtKB-AC map not found, upload manually as comppi_refseq_uniprot.csv"
fi 

cd ../../

###
# Caenorhabditis elegans
###

if [ ! -d ./ce ] 
then
    mkdir -p ./ce
fi

cd ./ce

### Localizations

if [ ! -d ./localization ] 
then
    mkdir -p ./localization
fi

cd ./localization

# Bacello
#if [ ! -f pred_cel ]
#then
#    wget http://gpcr.biocomp.unibo.it/bacello/datasets/C.elegans_annotations.zip -O ./bacello.zip
#    unzip ./bacello.zip
#    rm bacello.zip
#fi

# eSLDB
if [ ! -f eSLDB_Caenorhabditis_elegans.txt ]
then
    wget --post-data 'organism=Caenorhabditis+elegans&checkbox1_1=SI&checkbox1_2=SI&checkbox1_8=SI&f_type=plain&download=Download' \
    http://gpcr.biocomp.unibo.it/cgi-bin/predictors/esldb/full_download.cgi \
    -O ./eSLDB_Caenorhabditis_elegans.txt
fi

# GO
if [ ! -f go_ce.csv ]
then
    wget "ftp://ftp.ebi.ac.uk/pub/databases/GO/goa/WORM/gene_association.goa_worm.gz"
    gzip -d -N gene_association.goa_worm.gz
    mv gene_association.goa_worm go_ce.csv
fi

# Organelle
if [ ! -f organelle_ce.txt ]
then
    wget "http://organelledb.lsi.umich.edu/search-result.php?local=all&endoplasmic+reticulum=all&membrane+protein=all&miscellaneous+others=all&mitochondrion=all&nucleus=all&protein+complex=all&organism=worm&searchtype=full&geneAdv=&flat_file=Download+in+Flat+File" -O ./organelle_ce.txt
fi

# Pagosub
if [ ! -f caenorhabditis_elegans.csv ]
then
    wget http://webdocs.cs.ualberta.ca/%7Ebioinfo/PA/GOSUB/data/animal/caenorhabditis_elegans_csv.zip -O ./pagosub.zip
    unzip pagosub.zip
    rm pagosub.zip
fi

### Interactions

if [ ! -d ../interaction ] 
then
    mkdir -p ../interaction
fi

cd ../interaction

# Biogrid
# @see at the end of this file

# CCSB
if [ ! -f wi8.txt ]
then
    wget http://interactome.dfci.harvard.edu/C_elegans/graphs/sequence_edges/wi8.txt
fi

# DiP
# @see at the end of this file

# IntAct
# @see at the end of this file

# MINT
if [ ! -f 2013-03-26-mint-Caenorhabditis-binary.mitab26.txt ]
then
    wget "ftp://mint.bio.uniroma2.it/pub/release/mitab26/current/2013-03-26-mint-Caenorhabditis-binary.mitab26.txt"
fi

### Maps

if [ ! -d ../map ] 
then
    mkdir -p ../map
fi

cd ../map

check_comppi_custom ce comppi_worm_ensemblgeneid-uniprotkbac.txt
check_comppi_custom ce comppi_worm_ensemblpeptideid-uniprotkbac.txt
check_comppi_custom ce comppi_worm_entrezgene-uniprotkbac.txt
check_comppi_custom ce comppi_worm_refseq-uniprotkbac.txt
check_comppi_custom ce comppi_worm_wbgeneid-uniprotkbac.txt
check_comppi_custom ce comppi_worm_wormbase-uniprotkbac.txt
check_comppi_custom ce worm_organelle.csv
check_comppi_custom ce comppi_uniprot_deleted_ce.txt
check_comppi_custom ce comppi_ce_uniprotkb-ac_uniprotkb-swissprot.txt
check_comppi_custom ce comppi_ce_uniprotkb-ac_uniprotkb-trembl.txt

# Uniprot
if [ ! -f CAEEL_6239_idmapping_selected.tab ]
then
    wget "ftp://ftp.uniprot.org/pub/databases/uniprot/current_release/knowledgebase/idmapping/by_organism/CAEEL_6239_idmapping_selected.tab.gz" -O ./uniprot.gz
    gzip -d -N uniprot.gz
    rm uniprot.gz
fi

# UniprotFullname
if [ ! -f uniprot_ce_fullname.tab ] 
then
    wget "http://www.uniprot.org/uniprot/?query=organism%3a%22Caenorhabditis+elegans+%5b6239%5d%22&force=yes&format=tab&columns=id,entry%20name,reviewed,protein%20names" -O ./uniprot_ce_fullname.tab
fi

# UniprotSecondary
if [ ! -f sec_ac.txt ]
then
    ln -s ../../hs/map/sec_ac.txt sec_ac.txt
fi

# NetBiol WBGeneId -> uniprot
if [ ! -f "comppi_wbgeneid_uniprot.csv" ]
then
    echo "netbiol WBGeneId -> UniProtKB-AC mapping not found, upload it manually as 'comppi_wbgeneid_uniprot.csv'"
fi  

# Netbiol refseq->uniprot
if [ ! -f comppi_refseq_uniprot.csv ]
then
    echo "RefSeq->UniProtKB-AC map not found, upload manually as comppi_refseq_uniprot.csv"
fi 

cd ../../

###
# Saccaromicies Cervisae
###

if [ ! -d ./sc ] 
then
    mkdir -p ./sc
fi

cd ./sc

### Localizations

if [ ! -d ./localization ] 
then
    mkdir -p ./localization
fi

cd ./localization

# Bacello
#if [ ! -f pred_sce ]
#then
#    wget http://gpcr.biocomp.unibo.it/bacello/datasets/S.cerevisiae_annotations.zip -O ./bacello.zip
#    unzip ./bacello.zip
#    rm bacello.zip
#fi

# eSLDB
if [ ! -f eSLDB_Saccharomyces_cerevisiae.txt ]
then
    wget --post-data 'organism=Saccharomyces+cerevisiae&checkbox1_1=SI&checkbox1_2=SI&checkbox1_8=SI&f_type=plain&download=Download' \
    http://gpcr.biocomp.unibo.it/cgi-bin/predictors/esldb/full_download.cgi \
    -O ./eSLDB_Saccharomyces_cerevisiae.txt
fi

# GO
if [ ! -f go_sc.csv ]
then
    wget "ftp://ftp.ebi.ac.uk/pub/databases/GO/goa/YEAST/gene_association.goa_yeast.gz"
    gzip -d -N gene_association.goa_yeast.gz
    mv gene_association.goa_yeast go_sc.csv
fi  

# Organelle
if [ ! -f organelle_sc.txt ]
then
    wget "http://organelledb.lsi.umich.edu/search-result.php?local=all&endoplasmic+reticulum=all&membrane+protein=all&miscellaneous+others=all&mitochondrion=all&nucleus=all&protein+complex=all&organism=yeast&searchtype=full&geneAdv=&flat_file=Download+in+Flat+File" -O ./organelle_sc.txt
fi

# Pagosub
if [ ! -f saccharomyces_cerevisiae.csv ]
then
    wget http://webdocs.cs.ualberta.ca/%7Ebioinfo/PA/GOSUB/data/fungi/saccharomyces_cerevisiae_csv.zip -O ./pagosub.zip
    unzip pagosub.zip
    rm pagosub.zip
fi

# pTARGET
# TODO source site is down or moved

### Interactions

if [ ! -d ../interaction ] 
then
    mkdir -p ../interaction
fi

cd ../interaction

# Biogrid
# @see at the end of this file

# CCSB
if [ ! -f Y2H_union.txt ]
then
    wget http://interactome.dfci.harvard.edu/S_cerevisiae/download/Y2H_union.txt
fi

# ConsensusPathDB
#if [ ! -f ConsensusPathDB_yeast_PPI ]
#then
#    wget http://cpdb.molgen.mpg.de/download/ConsensusPathDB_yeast_PPI.gz -O ./consensus.gz
#    gzip -d -N ./consensus.gz
#    rm ./consensus.gz
#fi

# DiP
# @see at the end of this file

# IntAct
# @see at the end of this file

# MINT
if [ ! -f 2013-03-26-mint-Saccharomyces-binary.mitab26.txt ]
then
    wget "ftp://mint.bio.uniroma2.it/pub/release/mitab26/current/2013-03-26-mint-Saccharomyces-binary.mitab26.txt"
fi

### Maps

if [ ! -d ../map ] 
then
    mkdir -p ../map
fi

cd ../map

check_comppi_custom sc comppi_yeast_ensemblpeptideid-uniprotkbac.txt
check_comppi_custom sc yeast_organelle.csv
check_comppi_custom sc comppi_uniprot_deleted_sc.txt
check_comppi_custom sc comppi_sc_uniprotkb-ac_uniprotkb-swissprot.txt
check_comppi_custom sc comppi_sc_uniprotkb-ac_uniprotkb-trembl.txt

# Uniprot
if [ ! -f YEAST_559292_idmapping_selected.tab ]
then
    wget "ftp://ftp.uniprot.org/pub/databases/uniprot/current_release/knowledgebase/idmapping/by_organism/YEAST_559292_idmapping_selected.tab.gz" -O ./uniprot.gz
    gzip -d -N uniprot.gz
    rm uniprot.gz
fi

# UniprotFullname
if [ ! -f uniprot_sc_fullname.tab ] 
then
    wget "http://www.uniprot.org/uniprot/?query=organism%3a%22Saccharomyces+cerevisiae+%5b4932%5d%22&force=yes&format=tab&columns=id,entry%20name,reviewed,protein%20names" -O ./uniprot_sc_fullname.tab
fi

# UniprotSecondary
if [ ! -f sec_ac.txt ]
then
    ln -s ../../hs/map/sec_ac.txt sec_ac.txt
fi

# Netbiol refseq->uniprot
if [ ! -f comppi_refseq_uniprot.csv ]
then
    echo "RefSeq->UniProtKB-AC map not found, upload manually as comppi_refseq_uniprot.csv"
fi

cd ../../

### Other sources

# Biogrid
if [ ! -f ./hs/interaction/BIOGRID-ORGANISM-Homo_sapiens.tab2.txt ] \
|| [ ! -f ./dm/interaction/BIOGRID-ORGANISM-Drosophila_melanogaster.tab2.txt ] \
|| [ ! -f ./sc/interaction/BIOGRID-ORGANISM-Saccharomyces_cerevisiae.tab2.txt ] \
|| [ ! -f ./ce/interaction/BIOGRID-ORGANISM-Caenorhabditis_elegans.tab2.txt ]
then
    # download zip
    if [ ! -f BIOGRID-ORGANISM-3.2.111.tab2.zip ]
    then
        wget "http://thebiogrid.org/downloads/archives/Release%20Archive/BIOGRID-3.2.111/BIOGRID-ORGANISM-3.2.111.tab2.zip"
    fi
    
    # extract files
    unzip BIOGRID-ORGANISM-3.2.111.tab2.zip \
      BIOGRID-ORGANISM-Homo_sapiens-3.2.111.tab2.txt \
      BIOGRID-ORGANISM-Drosophila_melanogaster-3.2.111.tab2.txt \
      BIOGRID-ORGANISM-Saccharomyces_cerevisiae-3.2.111.tab2.txt \
      BIOGRID-ORGANISM-Caenorhabditis_elegans-3.2.111.tab2.txt
    
    mv BIOGRID-ORGANISM-Homo_sapiens-3.2.111.tab2.txt ./hs/interaction/BIOGRID-ORGANISM-Homo_sapiens.tab2.txt
    mv BIOGRID-ORGANISM-Drosophila_melanogaster-3.2.111.tab2.txt ./dm/interaction/BIOGRID-ORGANISM-Drosophila_melanogaster.tab2.txt
    mv BIOGRID-ORGANISM-Saccharomyces_cerevisiae-3.2.111.tab2.txt ./sc/interaction/BIOGRID-ORGANISM-Saccharomyces_cerevisiae.tab2.txt
    mv BIOGRID-ORGANISM-Caenorhabditis_elegans-3.2.111.tab2.txt ./ce/interaction/BIOGRID-ORGANISM-Caenorhabditis_elegans.tab2.txt
    
    # remove zip
    rm BIOGRID-ORGANISM-3.2.111.tab2.zip
fi

# Biogrid mapping
if [ ! -f ./hs/map/BIOGRID-IDENTIFIERS.tab.txt ] \
|| [ ! -f ./dm/map/BIOGRID-IDENTIFIERS.tab.txt ] \
|| [ ! -f ./sc/map/BIOGRID-IDENTIFIERS.tab.txt ] \
|| [ ! -f ./ce/map/BIOGRID-IDENTIFIERS.tab.txt ]
then
    # download zip
    if [ ! -f BIOGRID-IDENTIFIERS-3.2.111.tab.zip ]
    then
        wget "http://thebiogrid.org/downloads/archives/Release%20Archive/BIOGRID-3.2.111/BIOGRID-IDENTIFIERS-3.2.111.tab.zip"
    fi
    
    # extract file
    unzip BIOGRID-IDENTIFIERS-3.2.111.tab.zip
    
    # filter species
    grep "Homo sapiens" BIOGRID-IDENTIFIERS-3.2.111.tab.txt > ./hs/map/BIOGRID-IDENTIFIERS.tab.txt
    grep "Drosophila melanogaster" BIOGRID-IDENTIFIERS-3.2.111.tab.txt > ./dm/map/BIOGRID-IDENTIFIERS.tab.txt
    grep "Saccharomyces cerevisiae" BIOGRID-IDENTIFIERS-3.2.111.tab.txt > ./sc/map/BIOGRID-IDENTIFIERS.tab.txt
    grep "Caenorhabditis elegans" BIOGRID-IDENTIFIERS-3.2.111.tab.txt > ./ce/map/BIOGRID-IDENTIFIERS.tab.txt
    
    # remove zip
    rm BIOGRID-IDENTIFIERS-3.2.111.tab.zip
fi

# DiP
if [ ! -f ./hs/interaction/Dip_Hsapi.txt ] \
|| [ ! -f ./dm/interaction/Dip_Dmela.txt ] \
|| [ ! -f ./sc/interaction/Dip_Scere.txt ] \
|| [ ! -f ./ce/interaction/Dip_Celeg.txt ]
then
    # login
    wget --save-cookies dip-cookies.txt \
     --keep-session-cookies \
     --post-data 'login=vAirDa97&pass=Csbmq&lgn=1&Login=Login' \
     -U "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.14) Gecko/20080418 Ubuntu/7.10 (gutsy) Firefox/2.0.0.14" \
     http://dip.doe-mbi.ucla.edu/dip/Login.cgi \
     -O /dev/null
     
    # hs
    if [ ! -f ./hs/interaction/Dip_Hsapi.txt ]
    then
        wget --load-cookies dip-cookies.txt \
        -U "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.14) Gecko/20080418 Ubuntu/7.10 (gutsy) Firefox/2.0.0.14" \
        http://dip.doe-mbi.ucla.edu/dip/File.cgi?FN=2014/tab25/Hsapi20140427.txt \
        -O ./hs/interaction/Dip_Hsapi.txt
    fi
    
    # dm
    if [ ! -f ./dm/interaction/Dip_Dmela.txt ]
    then
        wget --load-cookies dip-cookies.txt \
        -U "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.14) Gecko/20080418 Ubuntu/7.10 (gutsy) Firefox/2.0.0.14" \
        http://dip.doe-mbi.ucla.edu/dip/File.cgi?FN=2014/tab25/Dmela20140427.txt \
        -O ./dm/interaction/Dip_Dmela.txt
    fi
    
    # sc
    if [ ! -f ./sc/interaction/Dip_Scere.txt ]
    then
        wget --load-cookies dip-cookies.txt \
        -U "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.14) Gecko/20080418 Ubuntu/7.10 (gutsy) Firefox/2.0.0.14" \
        http://dip.doe-mbi.ucla.edu/dip/File.cgi?FN=2014/tab25/Scere20140427.txt \
        -O ./sc/interaction/Dip_Scere.txt
    fi
    
    # ce
    if [ ! -f ./ce/interaction/Dip_Celeg.txt ]
    then
        wget --load-cookies dip-cookies.txt \
        -U "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.14) Gecko/20080418 Ubuntu/7.10 (gutsy) Firefox/2.0.0.14" \
        http://dip.doe-mbi.ucla.edu/dip/File.cgi?FN=2014/tab25/Celeg20140427.txt \
        -O ./ce/interaction/Dip_Celeg.txt
    fi
    
    # cleanup
    rm dip-cookies.txt
fi

# IntAct
if [ ! -f ./hs/interaction/intact-hs.txt ] \
|| [ ! -f ./dm/interaction/intact-dm.txt ] \
|| [ ! -f ./sc/interaction/intact-sc.txt ] \
|| [ ! -f ./ce/interaction/intact-ce.txt ]
then
    if [ ! -f ./intact.zip ]
    then
        wget ftp://ftp.ebi.ac.uk/pub/databases/intact/current/psimitab/intact.zip -O ./intact.zip
        unzip intact.zip
    fi     
    
    grep "taxid:9606(Homo sapiens)	taxid:9606(human)" intact.txt > ./hs/interaction/intact-hs.txt
    grep "taxid:7227(\"Drosophila melanogaster (Fruit fly)\")	taxid:7227(drome)" intact.txt > ./dm/interaction/intact-dm.txt
    grep "taxid:559292(\"Saccharomyces cerevisiae (strain ATCC 204508 / S288c)\")	taxid:559292(yeast)" intact.txt > ./sc/interaction/intact-sc.txt
    grep "taxid:6239(Caenorhabditis elegans)	taxid:6239(caeel)" intact.txt > ./ce/interaction/intact-ce.txt
    
    # cleanup
    rm intact.txt
    rm intact.zip 
fi
