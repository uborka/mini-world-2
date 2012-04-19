/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy.spreadsheet;

import java.util.Collections;
import java.util.List;
import java.util.Vector;

/**
 * Class wich represents a table row
 * @author henriqueloboweissmann
 */
public class Row implements Comparable {
    
    private Table table;
    
    public Table getTable() {return this.table;}
    
    private void setTable(Table tbl) {
        this.table = tbl;
    }
    
    private int order;
    
    public int getOrder() {return this.order;}
    
    public void setOrder(int value) {this.order = value;}
    
    public Row(Table tbl) {
        setTable(tbl);
        if (tbl != null) {
            getTable().addRow(this);
        }
    }
    
    private List<Cell> cells = new Vector<Cell>();

    public List<Cell> getCells() {
        return Collections.unmodifiableList(this.cells);
    }
    
    public void addCell(Cell cell) {
        if (cell != null && ! this.cells.contains(cell)) {
            this.cells.add(cell);
        }
    }
    
    public void removeCell(Cell cell) {
        if (cell != null && this.cells.contains(cell)) {
            this.cells.remove(cell);
        }
    }

    public int compareTo(Object o) {
        if (o != null && o instanceof Row) {
            Row row = (Row) o;
            if (getOrder() > row.getOrder())
                return 1;
            if (getOrder() < row.getOrder()) 
                return -1;
        }
        return 0;
    }
    
    public void sortCells() {
        java.util.Collections.sort(this.cells);
    }
    
}
