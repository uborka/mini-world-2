/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy.spreadsheet;

/**
 *
 * @author henriqueloboweissmann
 */
public enum CellDataType {

    Float("float"),
    Percentage("percentage"),
    String("string"),
    Boolean("boolean"),
    Currency("currency"),
    Time("time"),
    Date("date");
    
    private String value;
    
    public String getValue() {return this.value;}
    
    CellDataType(String value) {
        this.value = value;
    }
    
}
