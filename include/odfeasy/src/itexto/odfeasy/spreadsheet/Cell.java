/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy.spreadsheet;

/**
 * Class that represents a row's cell
 * @author henriqueloboweissmann
 */
public class Cell implements Comparable{

    private Row row;
    
    public Row getRow() {return this.row;}
    
    private void setRow(Row row) {
        this.row = row;
    }
    
    private int column;
    
    public int getColumn() {return this.column;}
    
    public void setColumn(int column) {
        this.column = column;
    }
    
    public Cell(Row row, int column) {
        setRow(row);
        setColumn(column);
        if (row != null) {
            row.addCell(this);
        }
    }
    
    private CellDataType dataType;
    
    public CellDataType getDataType() {
        if (this.dataType == null) {
            this.dataType = CellDataType.String;
        }
        return this.dataType;
    }
    
    public void setDataType(CellDataType type) {
        this.dataType = type;
    }
    
    private Object value;
    
    public Object getValue() {return this.value;}
    
    public void setValue(Object obj) {
        this.value = obj;
    }

    public int compareTo(Object o) {
        if (o != null && o instanceof Cell) {
            Cell cell = (Cell) o;
            if (getColumn() > cell.getColumn())
                return 1;
            if (getColumn() < cell.getColumn()) 
                return -1;
        }
        return 0;
    }
    
}
