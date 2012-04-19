/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy.spreadsheet;

import java.util.Collections;
import java.util.List;
import java.util.Vector;

/**
 * Class wich represents a table inside a spreadsheet
 * @author henriqueloboweissmann
 */
public class Table {

    private Spreadsheet spreadsheet;
    
    public Spreadsheet getSpreadsheet() {
        return this.spreadsheet;
    }
    
    private void setSpreadsheet(Spreadsheet sheet) {
        this.spreadsheet = sheet;
    }
    
    private String name;
    
    public String getName() {return this.name;}
    
    public void setName(String value) {
        this.name = value;
    }
    
    public Table(Spreadsheet sheet) {
        setSpreadsheet(sheet);
        if (sheet != null) {
            setName("Sheet" + Integer.toString(sheet.getTables().size() + 1));
            sheet.addTable(this);
        }
    }
    
    private List<Row> rows = new Vector<Row>();
    
    /**
     * Return an unmodifiable list containing all the table's rows
     * @return
     */
    public List<Row> getRows() {
        return Collections.unmodifiableList(this.rows);
    }
    
    public void addRow(Row row) {
        if (row != null && ! this.rows.contains(row)) {
            this.rows.add(row);
        }
    }
    
    public void removeRow(Row row) {
        if (row != null && this.rows.contains(row)) {
            this.rows.remove(row);
        }
    }
    
    private Row lastRow;
    
    public Row getLastRow() {
        
        if (lastRow == null && rows.size() > 0) {
            
            lastRow = rows.get(0);
            for (int i = 1; i < rows.size(); i++) {
                if (rows.get(i).getOrder() > lastRow.getOrder()) {
                    lastRow = rows.get(i);
                }
            }
        }
        
        return lastRow;
    }
    
    public void sortRows() {
        java.util.Collections.sort(this.rows);
        for (Row row : this.rows) {
            row.sortCells();
        }
    }
    
}
