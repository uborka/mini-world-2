/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy.spreadsheet;

import java.util.Collections;
import java.util.List;
import java.util.Vector;

/**
 * Class wich represents a single spreadsheet
 * @author henriqueloboweissmann
 */
public class Spreadsheet {

    private String name;
    
    public String getName() {
        return this.name;
    }
    
    public void setName(String value) {
        this.name = value;
    }
    
    private Document document;
    
    public Document getDocument() {return this.document;}
    
    private void setDocument(Document doc) {
        this.document = doc;
        if (doc != null) {
            this.getDocument().addSpreadsheet(this);
        }
    }
    
    public Spreadsheet(Document doc) {
        setDocument(doc);
    }
    
    private List<Table> tables = new Vector<Table>();
    
    /**
     * Return an unmodifiable list wich contains all the tables present on the Spreadsheet
     * @return
     */
    public List<Table> getTables() {
        return Collections.unmodifiableList(this.tables);
    }
    
    public void addTable(Table table) {
        if (table != null && ! this.tables.contains(table)) {
            this.tables.add(table);
        }
    }
    
    public void removeTable(Table table) {
        if (tables.contains(table)) {
            tables.remove(table);
        }
    }
    
}
