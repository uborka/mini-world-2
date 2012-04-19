/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy.spreadsheet;

import itexto.odfeasy.IODFDocument;
import java.util.Collections;
import java.util.List;
import java.util.Vector;

/**
 * The main spreadsheet document
 * @author henriqueloboweissmann
 */
public class Document implements IODFDocument {

    private Spreadsheet spreadsheet;
    
    /**
     * Return an unmodifiable list wich contains all the spreadsheets present
     * on the main document
     * @return
     */
    public Spreadsheet getSpreadsheet(){
        return this.spreadsheet;
    }
    
    public void addSpreadsheet(Spreadsheet sh) {
        this.spreadsheet = sh;
    }
    
    public void removeSpreadsheet() {
        this.spreadsheet = null;
    }

    private static final String type = "spreadsheet";
    
    public String getType() {
        return type;
    }
    
}
